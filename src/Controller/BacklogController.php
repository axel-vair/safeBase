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
        // return default entity manager (potter)
        $entityManager = $doctrine->getManager();

        // get the backuplog by id
        $backupLog = $backupLogRepository->find($id);

        // if backuplog id doesnt exist then throw an error
        if (!$backupLog) {
            throw $this->createNotFoundException('No backup log found for id ' . $id);
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
                $this->restoreService->restoreDatabase($filePath, $databaseName, $doctrine);
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
