<?php

namespace App\Controller;

use App\Entity\BackupLog;
use App\Form\RestoreDatabaseType;
use App\Repository\BackupLogRepository;
use App\Service\RestoreService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BacklogController extends AbstractController
{
    private RestoreService $restoreService;

    public function __construct(RestoreService $restoreService)
    {
        $this->restoreService = $restoreService;
    }

    #[Route('/backlog', name: 'app_backups')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // Get logs
        $backupInfoEntityManager = $doctrine->getManager('default');
        $backupLogRepository = $backupInfoEntityManager->getRepository(BackupLog::class);
        $backupLogs = $backupLogRepository->findAll();

        return $this->render('backlog/index.html.twig', [
            'backlogs' => $backupLogs,
        ]);
    }

    #[Route('/backlog/delete/{id}', name: 'app_backup_delete')]
    public function delete(int $id, ManagerRegistry $doctrine, BackupLogRepository $backupLogRepository): Response
    {
        $entityManager = $doctrine->getManager();
        $backupLog = $backupLogRepository->find($id);

        if (!$backupLog) {
            throw $this->createNotFoundException('No backup log found for id ' . $id);
        }
        $filePath = $backupLog->getFilePath();

        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                $this->addFlash('error', 'Erreur lors de la suppression du fichier.');
            }
        }

        $entityManager->remove($backupLog);
        $entityManager->flush();

        $this->addFlash('success', 'Le fichier de sauvegarde a été supprimé avec succès.');

        return $this->redirectToRoute('app_backups');
    }

    #[Route('/backlog/restore/{id}', name: 'app_backup_restore')]
    public function restoreForm(BackupLog $backupLog, ManagerRegistry $doctrine, Request $request): Response
    {
        $filePath = $backupLog->getFilePath();

        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de sauvegarde n\'existe pas.');
            return $this->redirectToRoute('app_backups');
        }

        $databases = ['potter', 'backup'];
        $form = $this->createForm(RestoreDatabaseType::class, null, ['databases' => $databases]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $databaseName = $data['database'];

            try {
                // Passer uniquement le nom du fichier à la méthode de restauration
                $this->restoreService->restoreDatabase(basename($filePath), $databaseName);
                $this->addFlash('success', 'La base de données a été restaurée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la restauration : ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_backups');
        }

        return $this->render('backlog/restore.html.twig', [
            'form' => $form->createView(),
            'backupLog' => $backupLog,
        ]);
    }

}
