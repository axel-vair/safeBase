<?php

namespace App\Controller;

use App\Entity\BackupLog;
use App\Repository\BackupLogRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BacklogController extends AbstractController
{
    #[Route('/backlog', name: 'app_backups')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $backupInfoEntityManager = $doctrine->getManager('backupinfo');
        $backupLogRepository = $backupInfoEntityManager->getRepository(BackupLog::class);
        $backupLogs = $backupLogRepository->findAll();

        return $this->render('backlog/index.html.twig', [
            'backlogs' => $backupLogs,
        ]);
    }
}
