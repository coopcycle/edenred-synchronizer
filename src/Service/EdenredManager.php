<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Merchant;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use League\Flysystem\FileAttributes;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;

class EdenredManager
{
    private $entityManager;
    private $logger;
    private $partnerName;
    private $sftpHost;
    private $sftpPort;
    private $sftpUsername;
    private $sftpPrivateKeyFile;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        string $partnerName,
        string $sftpHost,
        string $sftpPort,
        string $sftpUsername,
        string $sftpPrivateKeyFile
    )
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->partnerName = $partnerName;
        $this->sftpHost = $sftpHost;
        $this->sftpPort = $sftpPort;
        $this->sftpUsername = $sftpUsername;
        $this->sftpPrivateKeyFile = $sftpPrivateKeyFile;
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

        $xml = $this->createXML($merchants, $fullFileName, $date, $number);

        $file->setName($fullFileName);
        $this->entityManager->persist($file);

        $filesystem = new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                $this->sftpHost,
                $this->sftpUsername,
                null, // password
                $this->sftpPrivateKeyFile,
                null, // passphrase
                $this->sftpPort,
                false,
                30, // timeout (optional, default: 10)
                10, // max tries (optional, default: 4)
            ),
            '/sftp/IN', // path
        ));

        $filesystem->write($fullFileName, $xml);
    }

    public function readEdenredFileAndSynchronise()
    {
        $filesystem = new Filesystem(new SftpAdapter(
            new SftpConnectionProvider(
                $this->sftpHost,
                $this->sftpUsername,
                null, // password
                $this->sftpPrivateKeyFile,
                null, // passphrase
                $this->sftpPort,
                false,
                30, // timeout (optional, default: 10)
                10, // max tries (optional, default: 4)
            ),
            '/sftp/OUT', // path
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

    private function createXML(array $merchants, string $fileName, \DateTime $dateTime, string $number)
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
        $documentElement->setAttribute('nom', $fileName);

        $documentElement->setAttribute('ordre', $number); //Numero de sequence Padleft 0

        $parsedMerchants = 0;
        foreach ($merchants as $merchant) {
            $parsedMerchants++;
            $PDVElement = $xml->createElement('PDV');
            $PDVElement->setAttribute('Siret', $merchant->getSiret());
            $PDVElement->setAttribute('Addinfo', $merchant->getAddInfo());
            $PDVElement->setAttribute('Adresse', $merchant->getAddress());
            $PDVElement->setAttribute('Ville', $merchant->getCity());
            $PDVElement->setAttribute('CodePostal', $merchant->getPostalCode());
            $documentElement->appendChild($PDVElement);

            $this->entityManager->persist($merchant);
        }

        $documentElement->setAttribute('nombreAffilies', $parsedMerchants); // Nombre des affilies du fichier
        $xml->appendChild($documentElement);
        $xml->formatOutput = TRUE;

        return $xml->saveXML();
    }
}