<?php

namespace App\Tests;

use App\Controller\BacklogController;
use App\Entity\BackupLog;
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

        $restoreService = $this->createMock(RestoreService::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(EntityRepository::class);

        $doctrine->expects($this->once())
            ->method('getManager')
            ->with('backupinfo')
            ->willReturn($entityManager);

        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(BackupLog::class)
            ->willReturn($repository);

        $controller = $this->getMockBuilder(BacklogController::class)
            ->setConstructorArgs([$restoreService])
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('backlog/index.html.twig', ['backlogs' => []])
            ->willReturn(new Response());

        $response = $controller->index($doctrine);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testDelete()
    {
        $client = static::createClient();

        $restoreService = $this->createMock(RestoreService::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $backupLog = $this->createMock(BackupLog::class);
        $repository = $this->createMock(EntityRepository::class);

        $doctrine->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $backupLog->expects($this->once())
            ->method('getFilePath')
            ->willReturn('/path/to/file.sql');

        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($backupLog);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(BackupLog::class)
            ->willReturn($repository);

        $controller = $this->getMockBuilder(BacklogController::class)
            ->setConstructorArgs([$restoreService])
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Le fichier de sauvegarde a été supprimé avec succès.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('app_backups')
            ->willReturn(new RedirectResponse('/backups'));

        $response = $controller->delete(1, $doctrine);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/backups', $response->getTargetUrl());
    }
}
