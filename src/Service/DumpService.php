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
        $dumpFile = __DIR__ . '/../../var/dump/' . $name . '_dump_' . $formattedDateTime . '.sql';

        // Ensure the dump directory exists
        if (!is_dir(dirname($dumpFile))) {
            mkdir(dirname($dumpFile), 0777, true);
        }

        // Determine the container name and command based on the database type
        $port = '5432';
        $password = 'password';

        // Dynamically determine the database type
        switch ($name) {
            case 'potter':
                $containerName = 'safebase-database-1';
                break;
            case 'backup':
                $containerName = 'safebase-backup-1';
                break;
            default:
                if ($flashCallback) {
                    $flashCallback('error', 'Database not found: ' . htmlspecialchars($name));
                }
                return ''; // Return an empty string or handle it as needed
        }

//        // Build the command for mysqldump or pg_dump
//        if ($command === 'mysqldump') {
//            $command = sprintf(
//                'docker exec -t %s mysqldump -u %s -p%s --no-tablespaces -h %s %s > %s',
//                escapeshellarg($containerName),
//                escapeshellarg('user'),
//                escapeshellarg('password'),
//                escapeshellarg('localhost'),
//                escapeshellarg($name),
//                escapeshellarg($dumpFile)
//            );
//        } elseif ($command === 'pg_dump') {
            $command = sprintf(
                'docker exec -t %s sh -c "PGPASSWORD=%s pg_dump -U %s -h %s -p %s %s" > %s',
                escapeshellarg($containerName),
                escapeshellarg($password),
                escapeshellarg('user'),
                escapeshellarg($containerName),
                escapeshellarg($port),
                escapeshellarg($name),
                escapeshellarg($dumpFile)
            );


        // Execute the command
        exec($command, $output, $returnVar);

        // Handle the result of the command execution
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
