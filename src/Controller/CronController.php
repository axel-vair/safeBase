<?php

namespace App\Controller;

use App\Entity\Cron;
use App\Form\CronType;
use App\Repository\CronRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    #[Route('/cron', name: 'app_cron')]
    public function index(Request $request, CronRepository $cronRepository, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle configuration de cron
        $cronConfig = new Cron();

        // Créer le formulaire
        $form = $this->createForm(CronType::class, $cronConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrer la configuration dans la base de données
            $entityManager->persist($cronConfig);
            $entityManager->flush();

            // Mettre à jour le crontab avec la nouvelle fréquence
            $this->updateCrontab($cronConfig->getBackupFrequency());

            // Exécuter le script de sauvegarde
            $this->runBackupScript();

            // Afficher un message de succès
            $this->addFlash('success', 'Configuration du cron enregistrée avec succès et sauvegarde lancée.');
            return $this->redirectToRoute('app_cron');
        }

        return $this->render('cron/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/cron/manage', name: 'app_cron_manage')]
    public function manage(CronRepository $cronRepository): Response
    {
        // Récupérer toutes les configurations de cron
        $cronConfigs = $cronRepository->findAll();

        // Préparer les données pour la vue
        return $this->render('cron/manage.html.twig', [
            'cronJobs' => array_map(function(Cron $config) {
                return [
                    'id' => $config->getId(),
                    'command' => $config->getBackupFrequency(), // Ou toute autre méthode pour obtenir la commande
                ];
            }, $cronConfigs),
        ]);
    }

    #[Route('/cron/delete/{id}', name: 'app_cron_delete')]
    public function deleteCronJob(int $id, CronRepository $cronRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer la tâche cron correspondante dans la base de données
        $cronConfig = $cronRepository->find($id);

        if ($cronConfig) {
            try {
                // Suppression de l'entrée dans la base de données
                $entityManager->remove($cronConfig);
                $entityManager->flush();

                // Mettre à jour le crontab en supprimant la tâche correspondante
                $this->removeFromCrontab($cronConfig->getBackupFrequency());

                // Afficher un message de succès
                $this->addFlash('success', 'Tâche cron supprimée avec succès.');
            } catch (\Exception $e) {
                error_log('Erreur lors de la suppression : ' . $e->getMessage());
                // Afficher un message d'erreur à l'utilisateur
                $this->addFlash('error', 'Erreur lors de la suppression de la tâche cron.');
            }
        } else {
            // Afficher un message d'erreur si aucune tâche n'est trouvée
            $this->addFlash('error', 'Aucune tâche trouvée pour cet identifiant.');
        }

        return $this->redirectToRoute('app_cron_manage'); // Rediriger vers la page de gestion des crons
    }

    private function updateCrontab(string $frequency): void
    {
        // Définir la commande cron selon la fréquence
        switch ($frequency) {
            case 'daily':
                $cronCommand = "37 10 * * * /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            case 'weekly':
                $cronCommand = "37 10 * * 1 /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            case 'monthly':
                $cronCommand = "37 10 1 * * /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            default:
                return; // Sortir si aucune fréquence valide n'est trouvée.
        }

        // Écrire ou mettre à jour le crontab si nécessaire
        if ($cronCommand) {
            // Récupérer l'actuel crontab
            $currentCrontab = shell_exec('crontab -l');

            // Vérifier si la commande existe déjà pour éviter les doublons
            if (strpos($currentCrontab, trim($cronCommand)) === false) {
                file_put_contents('/tmp/crontab.txt', "$currentCrontab\n$cronCommand");

                exec('crontab /tmp/crontab.txt', $output, $returnVar);

                unlink('/tmp/crontab.txt'); // Supprimer le fichier temporaire

                if ($returnVar !== 0) {
                    error_log('Erreur lors de l\'exécution de crontab : ' . implode("\n", $output));
                }
            }
        }
    }

    private function removeFromCrontab(string $frequency): void
    {
        // Définir la commande cron selon la fréquence pour suppression
        switch ($frequency) {
            case 'daily':
                $cronCommand = "37 10 * * * /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            case 'weekly':
                $cronCommand = "37 10 * * 1 /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            case 'monthly':
                $cronCommand = "37 10 1 * * /bin/bash /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/backup_databases.sh >> /Users/axel/Documents/Lab/Workspace/Bachelor/safebase/var/cron/cron_backup.log 2>&1";
                break;
            default:
                return; // Sortir si aucune fréquence valide n'est trouvée.
        }

        // Récupérer l'actuel crontab et supprimer la commande correspondante
        if ($currentCrontab = shell_exec('crontab -l')) {
            // Filtrer pour supprimer le job spécifié
            $updatedCrontab = preg_replace("/^.*" . preg_quote(trim($cronCommand), '/') . ".*\$/m", '', trim($currentCrontab));

            file_put_contents('/tmp/crontab.txt', trim($updatedCrontab));

            exec('crontab /tmp/crontab.txt', $output, $returnVar);

            unlink('/tmp/crontab.txt'); // Supprimer le fichier temporaire

            if ($returnVar !== 0) {
                error_log('Erreur lors de l\'exécution de crontab : ' . implode("\n", $output));
            }
        }
    }

    private function runBackupScript(): void
    {
        try {
            // Récupérer le chemin du script de sauvegarde
            $scriptPath = $this->getParameter('kernel.project_dir') . '/backup_databases.sh';

            // Exécuter le script avec Process
            (new Process(['bash', $scriptPath]))->mustRun();

            // Afficher un message de succès si tout va bien
            $this->addFlash('success', 'Le script de sauvegarde a été exécuté avec succès.');

        } catch (ProcessFailedException | \Exception $exception) {
            // Afficher un message d'erreur en cas d'échec
            error_log('Erreur lors du lancement du script de sauvegarde : ' . htmlspecialchars($exception->getMessage()));

            // Afficher un message d'erreur à l'utilisateur
            $this->addFlash('error', 'Erreur lors du lancement du script de sauvegarde : ' . htmlspecialchars($exception->getMessage()));
        }
    }
}
