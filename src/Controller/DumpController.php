<?php

namespace App\Controller;

use App\Entity\BackupLog;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DumpController extends AbstractController
{
    #[Route('/dump/{name}', name: 'app_dump')]
    public function dump(EntityManagerInterface $entityManager, string $name, ManagerRegistry $doctrine): Response
    {
        return $this->dumpDatabase($entityManager, $name, $doctrine);
    }

    /**
     * This function is used for dumping the database.
     * We get information of the database then we get the date to version the file,
     * set the path to back up our file then launch the command to dump the database
     * @param EntityManagerInterface $entityManager
     * @param string $name
     * @param ManagerRegistry $doctrine
     * @return Response
     */
    private function dumpDatabase(EntityManagerInterface $entityManager, string $name, ManagerRegistry $doctrine): Response
    {
        $connection = $entityManager->getConnection();
        $params = $connection->getParams();

        // Date and hour to version the file
        $dateTime = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $formattedDateTime = $dateTime->format('d-m-Y H-i-s');

        // Path file dump with date and hour
        $dumpFile = __DIR__ . '/../../var/dump/' . $name . '_dump_' . $formattedDateTime . '.sql';

        // Ensure the dump directory exists
        if (!is_dir(dirname($dumpFile))) {
            mkdir(dirname($dumpFile), 0777, true);
        }

        // Determine the container name and command based on the database type
        $containerName = '';
        $command = '';
        $port = '';

        // Dynamically determine the database type
        switch ($name) {
            case 'backupinfo':
                $containerName = 'safebase-database-1';
                $port = '3306'; // MySQL port
                $command = 'mysqldump';
                break;
            case 'fixtures_db':
                $containerName = 'safebase-fixtures_db-1';
                $port = '3306'; // MySQL port
                $command = 'mysqldump';
                break;
            case 'backup':
                $containerName = 'safebase-backup-1';
                $port = '5432'; // PostgreSQL port
                $command = 'pg_dump';
                break;
            case 'backuptwo':
                $containerName = 'safebase-backuptwo-1';
                $port = '5459'; // PostgreSQL port
                $command = 'pg_dump';
                break;
            default:
                return new Response('Database not found: ' . htmlspecialchars($name), Response::HTTP_NOT_FOUND);
        }

        // Build the command dynamically
        if ($command === 'mysqldump') {
            $command = sprintf(
                'docker exec -t %s %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($containerName),
                escapeshellarg($command),
                escapeshellarg($params['user']),
                escapeshellarg($params['password']), // Assuming password is provided
                escapeshellarg($name),
                escapeshellarg($dumpFile)
            );
        } else if ($command === 'pg_dump') {
            $command = sprintf(
                'docker exec -t %s %s -U %s -h localhost -p %s %s > %s 2>&1',
                escapeshellarg($containerName),
                escapeshellarg($command),
                escapeshellarg($params['user']),
                escapeshellarg($port),
                escapeshellarg($name),
                escapeshellarg($dumpFile)
            );
        }

        // Execute the command
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->addFlash('error', 'Erreur lors du dump de la base de données: ' . implode("\n", $output));
        } else {
            if (filesize($dumpFile) === 0) {
                $this->addFlash('error', 'Le dump de la base de données est vide.');
            } else {
                $this->addFlash('success', 'Dump de la base de données créé avec succès : ' . basename($dumpFile));
            }
        }

        $this->dumpBackupLog($doctrine, $name, $dumpFile);

        return $this->redirectToRoute('app_default');
    }
    private function dumpBackupLog(ManagerRegistry $doctrine, string $name, string $dumpFile): void
    {
        $backupInfoEntityManager = $doctrine->getManager('backupinfo');

        $backupLog = new BackupLog();
        $backupLog->setDatabaseName($name);
        $backupLog->setFileName(basename($dumpFile));
        $backupLog->setFilePath($dumpFile);
        $backupLog->setCreatedAt(new \DateTimeImmutable());

        $backupInfoEntityManager->persist($backupLog);
        $backupInfoEntityManager->flush();
    }
}
