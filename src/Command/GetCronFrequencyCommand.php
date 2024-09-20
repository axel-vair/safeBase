<?php

namespace App\Command;

use App\Repository\CronRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:get-cron-frequency', description: 'Récupère la fréquence de sauvegarde.')]
class GetCronFrequencyCommand extends Command
{
    private $cronRepository;

    public function __construct(CronRepository $cronRepository)
    {
        parent::__construct();
        $this->cronRepository = $cronRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cronConfig = $this->cronRepository->findOneBy([]);

        if ($cronConfig) {
            $output->writeln($cronConfig->getBackupFrequency());
            return Command::SUCCESS;
        }

        $output->writeln('Aucune configuration trouvée.');
        return Command::FAILURE;
    }
}
