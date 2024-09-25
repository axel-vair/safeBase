<?php

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RestoreService
{
    public function restoreDatabase(string $filePath, string $databaseName): bool
    {
        // Déterminer le conteneur en fonction du nom de la base de données
        switch ($databaseName) {
            case 'potter':
                $containerName = "safebase-database-1";
                break;
            case 'backup':
                $containerName = 'safebase-backup-1';
                break;
            default:
                throw new \InvalidArgumentException("Base de données non reconnue : $databaseName");
        }

        // Commande pour restaurer la base de données
        $command = [
            '/usr/local/bin/docker/', 'exec', '-i', $containerName, 'psql', '-U', 'user', '-d', $databaseName
        ];

        // Créer un processus pour exécuter la commande
        $process = new Process($command);
        $process->setInput(file_get_contents($filePath));

        // Exécuter la commande
        $process->run();

        // Vérifier si la commande a réussi
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return true;
    }
}
