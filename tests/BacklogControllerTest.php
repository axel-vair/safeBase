<?php

namespace App\Tests;

use App\Controller\BacklogController;
use App\Entity\BackupLog;
use App\Repository\BackupLogRepository;
use App\Service\RestoreService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class BacklogControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        // Create mocks
        $restoreService = $this->createMock(RestoreService::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        // Configure the mocks
        $doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($entityManager);

        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(BackupLog::class)
            ->willReturn($repository);

        // Create the controller mock
        $controller = $this->getMockBuilder(BacklogController::class)
            ->setConstructorArgs([$restoreService])
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('backlog/index.html.twig', ['backlogs' => []])
            ->willReturn(new Response());

        // Call the index method
        $response = $controller->index($doctrine);

        // Assertions
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDelete()
    {
        $client = static::createClient();

        // Create mocks
        $restoreService = $this->createMock(RestoreService::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $backupLog = $this->createMock(BackupLog::class);
        $backupLogRepository = $this->createMock(BackupLogRepository::class);

        // Configure the mocks
        $doctrine->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $backupLog->expects($this->once())
            ->method('getFilePath')
            ->willReturn('/path/to/file.sql');

        $backupLogRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($backupLog);

        // Ensure getRepository is called correctly
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(BackupLog::class)
            ->willReturn($backupLogRepository);

        // Create the controller mock
        $controller = $this->getMockBuilder(BacklogController::class)
            ->setConstructorArgs([$restoreService])
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        // Expectations for flash messages and redirection
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Le fichier de sauvegarde a été supprimé avec succès.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_backups')
            ->willReturn(new RedirectResponse('/backups'));

        // Call the delete method with all required arguments
        $response = $controller->delete(1, $doctrine, $backupLogRepository);

        // Assertions
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/backups', $response->getTargetUrl());
    }
}
