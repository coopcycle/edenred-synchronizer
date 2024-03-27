<?php
namespace App\Controller;

use App\Dto\MerchantsDto;
use App\Entity\Merchant;
use App\Service\EdenredManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    ) {
        $this->entityManager = $entityManager;
        $this->edenredManager = $edenredManager;
    }

    public function __invoke(MerchantsDto $merchantsDto)
    {
        $this->edenredManager->createSyncFileAndSendToEdenred($merchantsDto->merchants);

        $this->entityManager->flush();

        return new JsonResponse('File created and sent to Edenred server');
    }

}