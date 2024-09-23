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
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Create new cron config
        $cronConfig = new Cron();

        // Create form
        $form = $this->createForm(CronType::class, $cronConfig);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Save config in the database
            $entityManager->persist($cronConfig);
            $entityManager->flush();

            // Update crontab with the new frequency
            $this->updateCrontab($cronConfig->getBackupFrequency());

            // Execute the script
            $this->runBackupScript();

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
        // Get all the cron configs
        $cronConfigs = $cronRepository->findAll();

        return $this->render('cron/manage.html.twig', [
            'cronJobs' => array_map(function(Cron $config) {
                return [
                    'id' => $config->getId(),
                    'command' => $config->getBackupFrequency(),
                ];
            }, $cronConfigs),
        ]);
    }

    #[Route('/cron/delete/{id}', name: 'app_cron_delete')]
    public function deleteCronJob(int $id, CronRepository $cronRepository, EntityManagerInterface $entityManager): Response
    {
        // Get cron task in the database
        $cronConfig = $cronRepository->find($id);

        if ($cronConfig) {
            try {
                // Delete cron task in database
                $entityManager->remove($cronConfig);
                $entityManager->flush();

                // Update the crontab
                $this->removeFromCrontab($cronConfig->getBackupFrequency());

                $this->addFlash('success', 'Tâche cron supprimée avec succès.');
            } catch (\Exception $e) {
                error_log('Erreur lors de la suppression : ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de la suppression de la tâche cron.');
            }
        } else {
            // Display error message if no task found
            $this->addFlash('error', 'Aucune tâche trouvée pour cet identifiant.');
        }

        return $this->redirectToRoute('app_cron_manage');
    }

    private function updateCrontab(string $frequency): void
    {
        // Define the frequency
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
                return;
        }

        // Write or update crontab if necessary
        if ($cronCommand) {
            // Get actual crontab
            $currentCrontab = shell_exec('crontab -l');

            // Verify if the command exists to avoid double
            if (strpos($currentCrontab, trim($cronCommand)) === false) {
                file_put_contents('/tmp/crontab.txt', "$currentCrontab\n$cronCommand");

                exec('crontab /tmp/crontab.txt', $output, $returnVar);

                unlink('/tmp/crontab.txt'); // Delete tmp file

                if ($returnVar !== 0) {
                    error_log('Erreur lors de l\'exécution de crontab : ' . implode("\n", $output));
                }
            }
        }
    }

    private function removeFromCrontab(string $frequency): void
    {
        // Defin the command to use depends to the frenquency
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
                return;
        }

        // Get actual crontab and delete command used
        if ($currentCrontab = shell_exec('crontab -l')) {
            // Filter to delete the good crontab
            $updatedCrontab = preg_replace("/^.*" . preg_quote(trim($cronCommand), '/') . ".*\$/m", '', trim($currentCrontab));

            file_put_contents('/tmp/crontab.txt', trim($updatedCrontab));

            exec('crontab /tmp/crontab.txt', $output, $returnVar);

            unlink('/tmp/crontab.txt'); // Delete tmp file

            if ($returnVar !== 0) {
                error_log('Erreur lors de l\'exécution de crontab : ' . implode("\n", $output));
            }
        }
    }

    private function runBackupScript(): void
    {
        try {
            // Get script path
            $scriptPath = $this->getParameter('kernel.project_dir') . '/backup_databases.sh';

            // Execute the script with Process
            (new Process(['bash', $scriptPath]))->mustRun();

            $this->addFlash('success', 'Le script de sauvegarde a été exécuté avec succès.');

        } catch (ProcessFailedException | \Exception $exception) {
            error_log('Erreur lors du lancement du script de sauvegarde : ' . htmlspecialchars($exception->getMessage()));

            $this->addFlash('error', 'Erreur lors du lancement du script de sauvegarde : ' . htmlspecialchars($exception->getMessage()));
        }
    }
}
