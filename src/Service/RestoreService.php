<?php

namespace App\Service;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RestoreService
{
    public function restoreDatabase(string $fileName, string $databaseName): bool
    {
        // Détermine le conteneur pour le dump
        $sourceContainer = "safebase-apache-php";
        $dumpPathInContainer = "/var/www/var/dump/$fileName";

        // Chemin de destination sur l'hôte
        $hostDumpPath = "/Users/axel/Documents/Lab/Study/Bachelor/safebase/backups/$fileName";

        // Vérifie si le dossier de sauvegarde existe, sinon crée-le
        if (!is_dir(dirname($hostDumpPath))) {
            mkdir(dirname($hostDumpPath), 0775, true);
        }

        // Copier le fichier depuis le conteneur vers l'hôte
        $copyCommand = [
            'docker', 'cp', "$sourceContainer:$dumpPathInContainer", $hostDumpPath
        ];

        $copyProcess = new Process($copyCommand);
        $copyProcess->run();

        if (!$copyProcess->isSuccessful()) {
            echo "Erreur lors de la copie : " . $copyProcess->getErrorOutput() . "\n";
            throw new ProcessFailedException($copyProcess);
        }

        // Vérifie que le fichier a été copié
        if (!file_exists($hostDumpPath)) {
            throw new \RuntimeException("Le fichier dump n'a pas été trouvé à l'emplacement : $hostDumpPath");
        }

        // Déterminer le conteneur cible pour la restauration
        switch ($databaseName) {
            case 'potter':
                $targetContainer = "safebase-database-1";
                break;
            case 'backup':
                $targetContainer = 'safebase-backup-1';
                break;
            default:
                throw new \InvalidArgumentException("Base de données non reconnue : $databaseName");
        }

        // Commande pour restaurer la base de données
        $restoreCommand = [
            'docker', 'exec', '-i', $targetContainer, 'psql', '-U', 'user', '-d', $databaseName, '-f', $hostDumpPath
        ];

        $restoreProcess = new Process($restoreCommand);
        $restoreProcess->run();

        if (!$restoreProcess->isSuccessful()) {
            throw new ProcessFailedException($restoreProcess);
        }

        return true;
    }
}
