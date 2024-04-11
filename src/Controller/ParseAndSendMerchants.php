<?php
namespace App\Controller;

use ApiPlatform\Validator\ValidatorInterface;
use App\Dto\MerchantsDto;
use App\Entity\Merchant;
use App\Service\EdenredManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ParseAndSendMerchants extends AbstractController
{
    private $edenredManager;
    private $entityManager;

    public function __construct(
        EdenredManager $edenredManager,
        EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->edenredManager = $edenredManager;
    }

    public function __invoke(MerchantsDto $merchantsDto)
    {
        $this->validator->validate($merchantsDto);

        $existingSirets = [];
        $merchantsToSend = [];
        foreach ($merchantsDto->merchants as $merchant) {
            $existingMerchant = $this->entityManager->getRepository(Merchant::class)->find($merchant->getSiret());
            if ($existingMerchant) {
                if (null !== $existingMerchant->getMerchantId()) {
                    throw new BadRequestException(sprintf("Merchant with siret %s already synchronized", $merchant->getSiret()));
                }
                $existingSirets[] = $merchant->getSiret();
            } else {
                $merchantsToSend[] = $merchant;
            }
        }

        if (count($merchantsToSend) === 0) {
            return new JsonResponse('All sent merchants have already been sent to Edenred');
        }

        $this->edenredManager->createSyncFileAndSendToEdenred($merchantsToSend);

        foreach($merchantsDto->merchants as $merchant) {
            if (!in_array($merchant->getSiret(), $existingSirets)) {
                $this->entityManager->persist($merchant);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse('File created and sent to Edenred server');
    }

}
