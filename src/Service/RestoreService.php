<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RestoreService
{
    public function restoreDatabase(string $filePath, string $databaseName, ManagerRegistry $doctrine): bool
    {
        // Déterminer le type de base de données et le conteneur Docker
        switch ($databaseName) {
            case 'backupinfo':
                $containerName = "safebase-database-1";
                $command = $this->buildPgRestoreCommand($containerName, $databaseName, $filePath);
                break;
            case 'backup':
                $containerName = 'safebase-backup-1';
                $command = $this->buildPgRestoreCommand($containerName, $databaseName, $filePath);
                break;
            case 'backuptwo':
                $containerName = 'safebase-backuptwo-1';
                $command = $this->buildPgRestoreCommand($containerName, $databaseName, $filePath);
                break;
            case 'fixtures_db':
                $containerName = 'safebase-fixtures_db-1';
                $command = $this->buildMysqlRestoreCommand($containerName, $databaseName, $filePath);
                break;
            case 'safebase':
                $containerName = 'safebase-safebase-1';
                $command = $this->buildPgRestoreCommand($containerName, $databaseName, $filePath);
                break;
            default:
                throw new \InvalidArgumentException("Base de données non reconnue : $databaseName");
        }

        // Exécuter la commande
        $process = Process::fromShellCommandline($command);
        $process->run();

        // Vérifier si la commande a réussi
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }
    private function buildPgRestoreCommand(string $containerName, string $databaseName, string $filePath): string
    {
        return sprintf(
            'docker exec -i %s psql -U user -d %s < %s',
            escapeshellarg($containerName),
            escapeshellarg($databaseName),
            escapeshellarg($filePath)
        );
    }

    private function buildMysqlRestoreCommand(string $containerName, string $databaseName, string $filePath): string
    {
        return sprintf(
            'docker exec -i %s mysql -u user -ppassword %s < %s',
            escapeshellarg($containerName),
            escapeshellarg($databaseName),
            escapeshellarg($filePath)
        );
    }
}
