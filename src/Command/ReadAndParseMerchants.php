<?php

namespace App\Command;

use App\Service\EdenredManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

Class ReadAndParseMerchants extends Command
{
    private $edenredManager;
    private $io;

    public function __construct(
        EdenredManager $edenredManager
    )
    {
        $this->edenredManager = $edenredManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('edenred:synchronizer:read')
            ->setDescription('Read XMLs from FTP server of Edenred for merchants synchronisation.')
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
        $this->io->text('Searching for XML files at Edenred FTP server for restaurants synchronisation');

        $this->edenredManager->readEdenredFileAndSynchronise();

        return 0;
    }

}
