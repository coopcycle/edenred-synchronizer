<?php

namespace App\Command;

use App\Entity\Merchant;
use App\Service\EdenredManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

Class FullSync extends Command
{
    private $io;

    public function __construct(
        private EdenredManager $edenredManager,
        private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('edenred:sync:all')
            ->setDescription('Re-sends all non-synchronized merchants.')
            ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $qb = $this->entityManager->getRepository(Merchant::class)->createQueryBuilder('m');
        $qb->andWhere($qb->expr()->isNull('m.merchantId'));

        $merchants = $qb->getQuery()->getResult();

        $this->edenredManager->createSyncFileAndSendToEdenred($merchants);

        return 0;
    }
}
