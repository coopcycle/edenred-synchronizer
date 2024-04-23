<?php

namespace App\Command;

use App\Entity\ApiClient;
use App\Service\EdenredManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

Class ListClientApiKeys extends Command
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
            ->setName('synchronizer:client:list')
            ->setDescription('List client API keys.')
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
        $apiClients = $this->entityManager->getRepository(ApiClient::class)->findAll();

        $rows = [];
        foreach ($apiClients as $apiClient) {
            $rows[] = [
                $apiClient->getName(),
                $apiClient->getApiKey()
            ];
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Name', 'API key'])
            ->setRows($rows)
        ;
        $table->render();

        return Command::SUCCESS;
    }

}

