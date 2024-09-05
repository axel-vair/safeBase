<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * Function that gets information of the databases Safebase, Backup and Backuptwo
     * @param ManagerRegistry $doctrine
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_default')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // Connection to the database Safebase
        $safebaseConnection = $doctrine->getConnection('default');
        $safebaseInfo = $this->getDatabaseInfo($safebaseConnection);

        // Connection to the database Backup
        $backupConnection = $doctrine->getConnection('backup');
        $backupInfo = $this->getDatabaseInfo($backupConnection);

        // Connection to the database Backup 2
        $backuptwoConnection = $doctrine->getConnection('backuptwo');
        $backuptwoInfo = $this->getDatabaseInfo($backuptwoConnection);

        return $this->render('default/index.html.twig', [
            'safebase' => $safebaseInfo,
            'backup' => $backupInfo,
            'backuptwo' => $backuptwoInfo,
        ]);
    }

    /**
     * Get database information
     * @param \Doctrine\DBAL\Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDatabaseInfo($connection): array
    {
        $isConnected = false;
        $errorMessage = '';

        try {
            $connection->connect();
            $isConnected = true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $params = $connection->getParams();
        $databaseName = $params['dbname'] ?? 'Nom de la base de données non défini';

        $tables = [];
        $columns = [];

        if ($isConnected) {
            $schemaManager = $connection->createSchemaManager();
            $allTables = $schemaManager->listTableNames();

            $excludedTables = ['doctrine_migration_versions', 'messenger_messages'];

            foreach ($allTables as $tableName) {
                if (!in_array($tableName, $excludedTables)) {
                    $tableColumns = $schemaManager->listTableColumns($tableName);
                    $columns[$tableName] = array_map(function ($column) {
                        return $column->getName();
                    }, $tableColumns);
                    $tables[] = $tableName;
                }
            }
        }

        return [
            'name' => $databaseName,
            'isConnected' => $isConnected,
            'error' => $errorMessage,
            'tables' => $tables,
            'columns' => $columns,
            'params' => $params,
        ];
    }
}
