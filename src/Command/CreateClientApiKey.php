<?php

namespace App\Command;

use App\Entity\ApiClient;
use App\Service\EdenredManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

Class CreateClientApiKey extends Command
{
    private $entityManager;
    private $io;

    public function __construct(
        EntityManagerInterface $entityManager
    )
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('synchronizer:client:create')
            ->addArgument('client', InputArgument::REQUIRED, 'Client name', null)
            ->setDescription('Creat an api key for a client.')
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
        $clientName = $input->getArgument('client');

        $this->io->text(sprintf('Creating an api key for client %s', $clientName));

        $apiClient = new ApiClient();
        $apiClient->setName($clientName);

        $key = hash('sha1', random_bytes(32));
        $apiClient->setApiKey($key);

        $this->entityManager->persist($apiClient);
        $this->entityManager->flush();

        $this->io->text(sprintf('Share this api key ak_%s with the client', $key));

        return 0;
    }

}
