<?php

namespace App\Service;

use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class DumpService
{
    public function dumpDatabase(EntityManagerInterface $entityManager, string $name, ManagerRegistry $doctrine, ?callable $flashCallback = null): string
    {
        // Date et heure pour versionner le fichier
        $dateTime = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $formattedDateTime = $dateTime->format('d-m-Y_H-i-s');

        // Chemin du fichier dump avec date et heure
        $dumpFile = '/var/www/var/dump/' . $name . '_dump_' . $formattedDateTime . '.sql';

        // Assurer que le répertoire de dump existe
        if (!is_dir(dirname($dumpFile))) {
            mkdir(dirname($dumpFile), 0755, true);
        }

        // Déterminer le nom du service basé sur le type de base de données
        switch ($name) {
            case 'potter':
                $containerName = 'database'; // Nom du service dans docker-compose
                break;
            case 'backup':
                $containerName = 'backup'; // Nom du service dans docker-compose
                break;
            default:
                if ($flashCallback) {
                    $flashCallback('error', 'Database not found: ' . htmlspecialchars($name));
                }
                return ''; // Retourner une chaîne vide ou gérer comme nécessaire
        }

        // Build the command for pg_dump
        $port = '5432';
        $password = 'password';
        $command = sprintf(
            'PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s > %s',
            escapeshellarg($password),
            escapeshellarg('user'),
            escapeshellarg($containerName),
            escapeshellarg($port),
            escapeshellarg($name),
            escapeshellarg($dumpFile)
        );

        // Exécuter la commande
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            if ($flashCallback) {
                $flashCallback('error', 'Erreur lors du dump de la base de données: ' . implode("\n", $output));
            }
        } else {
            if (filesize($dumpFile) === 0) {
                if ($flashCallback) {
                    $flashCallback('error', 'Le dump de la base de données est vide.');
                }
            } else {
                if ($flashCallback) {
                    $flashCallback('success', 'Dump de la base de données créé avec succès : ' . basename($dumpFile));
                }
            }
        }

        return $dumpFile; // Retourner le chemin du fichier dump
    }
}
