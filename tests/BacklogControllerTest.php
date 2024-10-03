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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    /**
     * @dataProvider deleteDataProvider
     */
    /**
     * @dataProvider deleteDataProvider
     */
    public function testDelete($backupLogExists, $expectedFlashMessage)
    {
        $client = static::createClient();

        $restoreService = $this->createMock(RestoreService::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $backupLog = $backupLogExists ? $this->createMock(BackupLog::class) : null;
        $backupLogRepository = $this->createMock(BackupLogRepository::class);

        $doctrine->method('getManager')->willReturn($entityManager);

        if ($backupLogExists) {
            $backupLog->method('getFilePath')->willReturn('/path/to/file.sql');
        }

        $backupLogRepository->method('find')->willReturn($backupLog);
        $entityManager->method('getRepository')->willReturn($backupLogRepository);

        $controller = $this->getMockBuilder(BacklogController::class)
            ->setConstructorArgs([$restoreService])
            ->onlyMethods(['addFlash', 'redirectToRoute', 'createNotFoundException'])
            ->getMock();

        if (!$backupLogExists) {
            $controller->expects($this->once())
                ->method('createNotFoundException')
                ->willReturn(new NotFoundHttpException('No backup log found for id 1'));

            $this->expectException(NotFoundHttpException::class);
        } else {
            $controller->expects($this->never())
                ->method('createNotFoundException');

            $controller->expects($this->once())
                ->method('addFlash')
                ->with($this->equalTo('success'), $this->equalTo($expectedFlashMessage));

            $controller->expects($this->once())
                ->method('redirectToRoute')
                ->with('app_backups')
                ->willReturn(new RedirectResponse('/backups'));
        }

        $controller->delete(1, $doctrine, $backupLogRepository);

        if ($backupLogExists) {
            $this->assertTrue(true);
        }
    }
    public function deleteDataProvider()
    {
        return [
            [true, 'Le fichier de sauvegarde a été supprimé avec succès.'],
            [false, null],
        ];
    }
}
