<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * Function that gets information of the databases Safebase, Backup, Backuptwo, and Fixtures
     * @param ManagerRegistry $doctrine
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_default')]
    public function index(ManagerRegistry $doctrine): Response
    {

        // Connection to the database Potter
        $potterConnection = $doctrine->getConnection('default');
        $potterDb = $this->getDatabaseInfo($potterConnection);

        // Connection to the backup database
        $backupConnection = $doctrine->getConnection('backup');
        $backupDb = $this->getDatabaseInfo($backupConnection);

        return $this->render('default/index.html.twig', [
            'potterDb' => $potterDb,
            'backupDb' => $backupDb,
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
