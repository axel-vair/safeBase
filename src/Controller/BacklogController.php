<?php

namespace App\Controller;

use App\Entity\BackupLog;
use App\Form\RestoreDatabaseType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BacklogController extends AbstractController
{
    #[Route('/backlog', name: 'app_backups')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // Get logs
        $backupInfoEntityManager = $doctrine->getManager('backupinfo');
        $backupLogRepository = $backupInfoEntityManager->getRepository(BackupLog::class);
        $backupLogs = $backupLogRepository->findAll();

        return $this->render('backlog/index.html.twig', [
            'backlogs' => $backupLogs,
        ]);
    }

    #[Route('/backlog/restore/{id}', name: 'app_backup_restore')]
    public function restoreForm(BackupLog $backupLog, ManagerRegistry $doctrine, Request $request): Response
    {
        // Get backuplog file path
        $filePath = $backupLog->getFilePath();

        // Verifu if file existe
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de sauvegarde n\'existe pas.');
            return $this->redirectToRoute('app_backups');
        }

        // Get all databases that can be found
        $databases = $this->getDatabases($doctrine);

        // Create for to select in which database restore the sql file
        $form = $this->createForm(RestoreDatabaseType::class, null, ['databases' => $databases]);

        // Handle form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $databaseName = $data['database'];

            // Call method to restore the file in chosen database
            return $this->restore($backupLog, $doctrine, $databaseName);
        }

        return $this->render('backlog/restore.html.twig', [
            'form' => $form->createView(),
            'backupLog' => $backupLog,
        ]);
    }

    #[Route('/backlog/delete/{id}', name: 'app_backup_delete')]
    public function delete(int $id, ManagerRegistry $doctrine): Response
    {
        // return default entity manager (backupinfo)
        $entityManager = $doctrine->getManager();

        // get the backuplog by id
        $backupLog = $entityManager->getRepository(BackupLog::class)->find($id);

        // if backuplog id doesnt exist then throw an error
        if (!$backupLog) {
            throw $this->createNotFoundException('No backup log found for id '.$id);
        }

        // stock in filetpath backlog path
        $filePath = $backupLog->getFilePath();

        // if file exists then delete the file
        if (file_exists($filePath)) {
            unlink($filePath);
            $entityManager->remove($backupLog);
            $entityManager->flush();
        }

        if (!$entityManager->contains($backupLog)) {
            $entityManager->persist($backupLog);
            $entityManager->flush();
        }

        $entityManager->remove($backupLog);
        $entityManager->flush();

        $this->addFlash('success', 'Le fichier de sauvegarde a été supprimé avec succès.');

        return $this->redirectToRoute('app_backups');
    }

    /**
     * Method used to get all databases
     * @param ManagerRegistry $doctrine
     * @return array
     */
    private function getDatabases(ManagerRegistry $doctrine): array
    {
        $connection = $doctrine->getConnection();
        $databases = $connection->fetchAllAssociative('SHOW DATABASES');
        dump($databases);

        return array_column($databases, 'Database');
    }

    /**
     * Method to restore sql file
     * @param BackupLog $backupLog
     * @param ManagerRegistry $doctrine
     * @param string $databaseName
     * @return Response
     */
    private function restore(BackupLog $backupLog, ManagerRegistry $doctrine, string $databaseName): Response
    {
        // Get file path
        $filePath = $backupLog->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de sauvegarde n\'existe pas.');
            return $this->redirectToRoute('app_backups');
        }

        // Execute restore
        try {
            $connection = $doctrine->getConnection();
            $connection->exec("SET NAMES 'utf8mb4'");

            // Read file
            $sql = file_get_contents($filePath);

            // Split SQL commands by semicolon
            $commands = explode(';', $sql);

            foreach ($commands as $command) {
                $command = trim($command);
                if (!empty($command)) {
                    $connection->exec($command);
                }
            }

            $connection->commit();
            $this->addFlash('success', 'Restauration réussie !');
        } catch (\Exception $e) {
            $connection->rollBack(); // End transaction
            $this->addFlash('error', 'Erreur lors de la restauration : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_backups');
    }
}
