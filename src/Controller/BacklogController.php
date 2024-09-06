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
        // Récupérer le gestionnaire d'entités pour la connexion backupinfo
        $backupInfoEntityManager = $doctrine->getManager('backupinfo');

        // Récupérer le repository pour BackupLog
        $backupLogRepository = $backupInfoEntityManager->getRepository(BackupLog::class);

        // Récupérer tous les logs de sauvegarde
        $backupLogs = $backupLogRepository->findAll();

        return $this->render('backlog/index.html.twig', [
            'backlogs' => $backupLogs,
        ]);
    }
}
