<?php

namespace App\Service;

use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class DumpService
{
    public function dumpDatabase(EntityManagerInterface $entityManager, string $name, ManagerRegistry $doctrine, ?callable $flashCallback = null): string
    {
        // Date and hour to version the file
        $dateTime = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $formattedDateTime = $dateTime->format('d-m-Y_H-i-s');

        // Path file dump with date and hour
        $dumpFile = '/var/www/var/dump/' . $name . '_dump_' . $formattedDateTime . '.sql';

        // Ensure the dump directory exists
        if (!is_dir(dirname($dumpFile))) {
            mkdir(dirname($dumpFile), 0755, true);
        }

        // Determine the container name and command based on the database type
        $port = '5432';
        $password = 'password'; // Assurez-vous que ce mot de passe est correct

        switch ($name) {
            case 'potter':
                $containerName = 'safebase_database_1'; // Vérifiez le nom exact du conteneur
                break;
            case 'backup':
                $containerName = 'safebase_backup_1'; // Vérifiez le nom exact du conteneur
                break;
            default:
                if ($flashCallback) {
                    $flashCallback('error', 'Database not found: ' . htmlspecialchars($name));
                }
                return ''; // Return an empty string or handle it as needed
        }

        // Build the command for pg_dump
        $command = sprintf(
            'docker exec -t %s sh -c "PGPASSWORD=%s pg_dump -U %s -h localhost -p %s %s" > %s',
            escapeshellarg($containerName),
            escapeshellarg($password),
            escapeshellarg('user'),
            escapeshellarg($port),
            escapeshellarg($name),
            escapeshellarg($dumpFile)
        );

        // Execute the command
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

        return $dumpFile; // Return the path to the dump file
    }
}
