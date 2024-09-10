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
        // Récupérer les logs de sauvegarde
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
        // Récupérer le chemin du fichier SQL
        $filePath = $backupLog->getFilePath();

        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de sauvegarde n\'existe pas.');
            return $this->redirectToRoute('app_backups');
        }

        // Récupérer les bases de données disponibles
        $databases = $this->getDatabases($doctrine);

        // Créer le formulaire
        $form = $this->createForm(RestoreDatabaseType::class, null, ['databases' => $databases]);

        // Gérer la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $databaseName = $data['database'];

            // Appeler la méthode de restauration
            return $this->restore($backupLog, $doctrine, $databaseName);
        }

        return $this->render('backlog/restore.html.twig', [
            'form' => $form->createView(),
            'backupLog' => $backupLog,
        ]);
    }

    // Méthode pour récupérer les bases de données
    private function getDatabases(ManagerRegistry $doctrine): array
    {
        $connection = $doctrine->getConnection();
        $databases = $connection->fetchAllAssociative('SHOW DATABASES');

        return array_column($databases, 'Database');
    }

    // Méthode pour restaurer la base de données
    private function restore(BackupLog $backupLog, ManagerRegistry $doctrine, string $databaseName): Response
    {
        // Récupérer le chemin du fichier SQL
        $filePath = $backupLog->getFilePath();

        // Vérifier si le fichier existe
        if (!file_exists($filePath)) {
            $this->addFlash('error', 'Le fichier de sauvegarde n\'existe pas.');
            return $this->redirectToRoute('app_backups');
        }

        // Exécuter la restauration
        try {
            $sql = file_get_contents($filePath);
            $connection = $doctrine->getConnection();

            // Utiliser une transaction pour assurer la cohérence
            $connection->beginTransaction();
            $connection->exec("USE $databaseName;"); // Sélectionner la base de données
            $connection->exec($sql); // Exécuter le fichier SQL
            $connection->commit();

            $this->addFlash('success', 'Restauration réussie !');
        } catch (\Exception $e) {
            $connection->rollBack(); // Annuler la transaction en cas d'erreur
            $this->addFlash('error', 'Erreur lors de la restauration : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_backups');
    }
}
