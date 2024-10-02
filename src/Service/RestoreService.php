<?php
namespace App\Service;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class RestoreService
{
    public function restoreDatabase(string $fileName, string $databaseName): bool
    {
        // Détermine le conteneur cible pour la restauration
        $targetContainer = $this->getTargetContainer($databaseName);
        $password = 'password';

        // Commande pour restaurer la base de données avec le mot de passe
        $restoreCommand = [
            'sh', '-c', "PGPASSWORD='$password' psql -h $targetContainer -U user -d $databaseName -f /var/www/var/dump/$fileName"
        ];

        $this->executeProcess($restoreCommand);

        return true;
    }

    private function getTargetContainer(string $databaseName): string
    {
        switch ($databaseName) {
            case 'potter':
                return "safebase-database-1";
            case 'backup':
                return "safebase-backup-1";
            default:
                throw new \InvalidArgumentException("Base de données non reconnue : $databaseName");
        }
    }

    private function executeProcess(array $command): void
    {
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
