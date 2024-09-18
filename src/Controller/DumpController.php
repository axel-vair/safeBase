<?php

namespace App\Controller;

use App\Entity\BackupLog;
use App\Service\DumpService;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DumpController extends AbstractController
{
    private DumpService $dumpService;

    public function __construct(DumpService $dumpService)
    {
        $this->dumpService = $dumpService;
    }

    #[Route('/dump/{name}', name: 'app_dump')]
    public function dump(EntityManagerInterface $entityManager, string $name, ManagerRegistry $doctrine): Response
    {
        $dumpFile = $this->dumpService->dumpDatabase($entityManager, $name, $doctrine, function($type, $message) {
            $this->addFlash($type, $message);
        });

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

        $dateTime = new \DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));

        $backupLog->setCreatedAt($dateTime);

        $backupInfoEntityManager->persist($backupLog);
        $backupInfoEntityManager->flush();
    }
}
