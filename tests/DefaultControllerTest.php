<?php

namespace App\Tests;

use App\Controller\DefaultController;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $doctrine = $this->createMock(ManagerRegistry::class);

        $connections = [
            'backup' => $this->createMock(Connection::class),
            'default' => $this->createMock(Connection::class),
        ];

        $doctrine->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturnCallback(function ($name) use ($connections) {
                return $connections[$name];
            });

        foreach ($connections as $connection) {
            $connection->expects($this->once())->method('connect');
            $connection->expects($this->once())->method('getParams')->willReturn(['dbname' => 'test_db']);
            $schemaManager = $this->createMock(AbstractSchemaManager::class);
            $schemaManager->expects($this->once())->method('listTableNames')->willReturn([]);
            $connection->expects($this->once())->method('createSchemaManager')->willReturn($schemaManager);
        }

        $controller = $this->getMockBuilder(DefaultController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('default/index.html.twig', $this->isType('array'))
            ->willReturn(new Response());

        $response = $controller->index($doctrine);

        $this->assertInstanceOf(Response::class, $response);
    }
}
