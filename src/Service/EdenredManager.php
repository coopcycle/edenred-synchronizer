<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Merchant;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToWriteFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class EdenredManager
{
    private $entityManager;
    private $logger;
    private $partnerName;
    private $sftpConnectionProvider;
    private $sftpReadDirectory;
    private $s3Storage;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        SftpConnectionProvider $sftpConnectionProvider,
        string $partnerName,
        string $sftpReadDirectory,
        FilesystemOperator $s3Storage,
        private FilesystemOperator $sftpWriteStorage
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->sftpConnectionProvider = $sftpConnectionProvider;
        $this->partnerName = $partnerName;
        $this->sftpReadDirectory = $sftpReadDirectory;
        $this->s3Storage = $s3Storage;
    }

    public function createSyncFileAndSendToEdenred(array $merchants): void
    {
        $date = new \DateTime();

        $fileName = sprintf('%s_RAEN_TRDQ_%s_%s', $this->partnerName, $date->format('Ymd'), $date->format('His'));
        $file = new File($fileName);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        $number = $file->getId();
        $fullFileName = sprintf('%s_%s.xml', $fileName, $number);

        $xml = $this->createXML($merchants, sprintf('%s_RAEN', $this->partnerName), $date, $number);

        $file->setName($fullFileName);
        $this->entityManager->persist($file);

        try {
            $this->s3Storage->write(sprintf('sent/%s', $fullFileName), $xml);
            $this->sftpWriteStorage->write($fullFileName, $xml);
            $file->setSent(true);
        } catch (UnableToWriteFile $e) {
            $file->setErrors($e->getMessage());
            $this->entityManager->persist($file);
            $this->entityManager->flush();
            throw $e;
        }
    }

    public function readEdenredFileAndSynchronise()
    {
        $filesystem = new Filesystem(new SftpAdapter(
            $this->sftpConnectionProvider,
            $this->sftpReadDirectory, // path
        ));

        $allPaths = $filesystem->listContents('')
            ->filter(fn (StorageAttributes $attributes) => $attributes->isFile())
            ->filter(fn (FileAttributes $attributes) =>
                str_starts_with($attributes->path(), sprintf('RAEN_%s_TRDQ_', strtoupper($this->partnerName))) & $attributes->fileSize() > 0)
            ->map(fn (FileAttributes $attributes) => $attributes->path())
            ->toArray();

        $this->logger->info(sprintf('%d files at Edenred SFTP for sync', count($allPaths)));

        $contents = array_map(function($path) use($filesystem) {
            $this->logger->info(sprintf('Reading content from file "%s"', $path));
            return $filesystem->read($path);
        }, $allPaths);

        foreach ($contents as $content) {
            $document = new DOMDocument();
            $document->loadXML($content);

            $merchants = $document->getElementsByTagName('PDV');

            foreach ($merchants as $merchantContent) {
                $this->logger->info(sprintf('Reading merchant with SIRET "%s"', $merchantContent->getAttribute('Siret')));

                    $merchant = $this->entityManager->getRepository(Merchant::class)
                        ->find(intval($merchantContent->getAttribute('Siret')));

                    if (!$merchant) {
                        $this->logger->error(sprintf('Merchant with SIRET "%s" not found', $merchantContent->getAttribute('Siret')));
                    } else {
                        if ($merchantContent->hasAttribute('MID')) {
                            $this->logger->info(sprintf('Merchant ID "%s"', $merchantContent->getAttribute('MID')));

                            $merchant->setMerchantId($merchantContent->getAttribute('MID'));

                            if ($merchantContent->hasAttribute('StatutMID')) {
                                $this->logger->info('Merchant already enabled for using TR cards');
                                $merchant->setAcceptsTRCard($merchantContent->getAttribute('StatutMID') === "1");
                            } else {
                                $this->logger->info('Merchant not yet enabled for using TR cards');
                            }
                        } else {
                            $this->logger->info('Merchant ID not yet available');
                        }

                        if ($merchantContent->hasAttribute('Adresse')) {
                            $merchant->setAddress($merchantContent->getAttribute('Adresse'));
                        }
                        if ($merchantContent->hasAttribute('Ville')) {
                            $merchant->setCity($merchantContent->getAttribute('Ville'));
                        }
                        if ($merchantContent->hasAttribute('CodePostal')) {
                            $merchant->setPostalCode($merchantContent->getAttribute('CodePostal'));
                        }

                        $this->entityManager->flush();
                    }
            }
        }
    }

    private function createXML(array $merchants, string $name, \DateTime $dateTime, string $number)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');

        $documentElement = $xml->createElement('DOCUMENT');
        $documentElement->setAttributeNS(
            'http://www.w3.org/2000/xmlns/', // xmlns namespace URI
            'xmlns:xsd',
            'http://www.w3.org/2001/XMLSchema'
        );
        $documentElement->setAttributeNS(
            'http://www.w3.org/2000/xmlns/', // xmlns namespace URI
            'xmlns:xsi',
            'http://www.w3.org/2001/XMLSchema-instance'
        );
        $documentElement->setAttribute('tpFlux', 'TRDQ');
        $documentElement->setAttribute('source', $this->partnerName); // Nom de MarketPlace OU RAEN
        $documentElement->setAttribute('destination', 'RAEN');

        $documentElement->setAttribute('date', $dateTime->format('YmdHis')); // Date execution batch YYYYMMDDHHmmss

        /*
        <!-- Nom du fichier NomMarketPlace_RAEN_TRDQ_AAAAMMJJ_HHNNSS_NumeroOrdre.xml-->
        */
        $documentElement->setAttribute('nom', $name);

        $documentElement->setAttribute('ordre', $number); //Numero de sequence Padleft 0

        $parsedMerchants = 0;
        foreach ($merchants as $merchant) {
            $existingMerchant = $this->entityManager->getRepository(Merchant::class)->find($merchant->getSiret());

            if ($existingMerchant && null !== $existingMerchant->getMerchantId()) {
                throw new BadRequestException(sprintf("Merchant with siret %s already synchronized", $merchant->getSiret()));
            }

            $parsedMerchants++;
            $PDVElement = $xml->createElement('PDV');
            $PDVElement->setAttribute('Siret', $merchant->getSiret());
            if (!empty($merchant->getAddInfo())) {
                $PDVElement->setAttribute('Addinfo', $merchant->getAddInfo());
            }
            if (!empty($merchant->getAddress())) {
                $PDVElement->setAttribute('Adresse', $merchant->getAddress());
            }
            if (!empty($merchant->getCity())) {
                $PDVElement->setAttribute('Ville', $merchant->getCity());
            }
            if (!empty($merchant->getPostalCode())) {
                $PDVElement->setAttribute('CodePostal', $merchant->getPostalCode());
            }
            $documentElement->appendChild($PDVElement);
        }

        $documentElement->setAttribute('nombreAffilies', $parsedMerchants); // Nombre des affilies du fichier
        $xml->appendChild($documentElement);
        $xml->formatOutput = TRUE;

        return $xml->saveXML();
    }
}
