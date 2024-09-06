<?php

namespace App\Controller;

use App\Entity\BackupLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DumpController extends AbstractController
{
    #[Route('/dump/{name}', name: 'app_dump')]
    public function dump(EntityManagerInterface $entityManager, string $name): Response
    {
        return $this->dumpDatabase($entityManager, $name);
    }

    /**
     * This function is used for dumping the database.
     * We get information of the database then we get the date to version the file,
     * set the path to back up our file then launch the command to dump the database
     * @param EntityManagerInterface $entityManager
     * @param string $name
     * @return Response
     */
    private function dumpDatabase(EntityManagerInterface $entityManager, string $name): Response
    {
        $connection = $entityManager->getConnection();
        $params = $connection->getParams();

        // Get information of connection
        $user = $params['user'];

        // Date and hour to version the file
        $dateTime = new \DateTime();
        $formattedDateTime = $dateTime->format('Y-m-d_H-i-s'); // Format: YYYY-MM-DD_HH-MM-SS

        // Path file dump with date and hour
        $dumpFile = __DIR__ . '/../../var/dump/' . $name . '_dump_' . $formattedDateTime . '.sql';

        // Ensure the dump directory exists
        if (!is_dir(dirname($dumpFile))) {
            mkdir(dirname($dumpFile), 0777, true);
        }

        // Determine the container and port based on the database name
        $port = '5432';
        switch ($name) {
            case 'safebase':
                $containerName = 'safebase-database-1';
                break;
            case 'backup':
                $containerName = 'safebase-backup-1';
                $user = 'backup';
                break;
            case 'backuptwo':
                $containerName = 'safebase-backuptwo-1';
                $user = 'backuptwo';
                break;
            default:
                return new Response('Database not found: ' . htmlspecialchars($name), Response::HTTP_NOT_FOUND);
        }

        // Command to dump the database
        $command = sprintf(
            'docker exec -t %s pg_dump -U %s -h localhost -p %s %s > %s',
            escapeshellarg($containerName),
            escapeshellarg($user),
            escapeshellarg($port),
            escapeshellarg($name), // Use the name passed in the URL
            escapeshellarg($dumpFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->addFlash('error', 'Erreur lors du dump de la base de données: ' . implode("\n", $output), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->dumpBackupLog($entityManager, $name, $dumpFile);

        $this->addFlash('success', 'Dump de la base de données créé avec succès : ' . basename($dumpFile));

        return $this->redirectToRoute('app_default');
    }

    private function dumpBackupLog(EntityManagerInterface $entityManager, string $name, string $dumpFile): void
    {
        $backupLog = new BackupLog();
        $backupLog->setDatabaseName($name);
        $backupLog->setFileName(basename($dumpFile));
        $backupLog->setFilePath($dumpFile);
        $backupLog->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($backupLog);
        $entityManager->flush();
    }
}
