<?php

namespace App\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * Fonction qui récupère les informations des bases de données Safebase, Backup, Backuptwo et Fixtures.
     *
     * @param ManagerRegistry $doctrine
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_default')]
    public function index(ManagerRegistry $doctrine): Response
    {
        // Connexion à la base de données Potter via le service 'default'
        $potterConnection = $doctrine->getConnection('default');
        $potterDb = $this->getDatabaseInfo($potterConnection);

        // Connexion à la base de données de sauvegarde
        $backupConnection = $doctrine->getConnection('backup');
        $backupDb = $this->getDatabaseInfo($backupConnection);

        return $this->render('default/index.html.twig', [
            'potterDb' => $potterDb,
            'backupDb' => $backupDb,
        ]);
    }

    /**
     * Récupère les informations sur la base de données.
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDatabaseInfo($connection): array
    {
        $isConnected = false;
        $errorMessage = '';

        try {
            // Essaye de se connecter à la base de données
            $connection->connect();
            $isConnected = true;
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        // Récupération des paramètres de connexion
        $params = $connection->getParams();
        $databaseName = $params['dbname'] ?? 'Nom de la base de données non défini';

        // Initialisation des tableaux pour les tables et colonnes
        $tables = [];
        $columns = [];

        if ($isConnected) {
            // Récupération des informations sur le schéma
            $schemaManager = $connection->createSchemaManager();
            $allTables = $schemaManager->listTableNames();

            // Tables à exclure
            $excludedTables = ['doctrine_migration_versions', 'messenger_messages'];

            foreach ($allTables as $tableName) {
                if (!in_array($tableName, $excludedTables)) {
                    // Récupération des colonnes pour chaque table
                    $tableColumns = $schemaManager->listTableColumns($tableName);
                    $columns[$tableName] = array_map(function ($column) {
                        return $column->getName();
                    }, $tableColumns);
                    $tables[] = $tableName;
                }
            }
        }

        return [
            'name' => !empty($databaseName) ? $databaseName : 'Nom de la base de données non défini',
            'isConnected' => $isConnected,
            'error' => $errorMessage,
            'tables' => $tables,
            'columns' => $columns,
            'params' => $params,
        ];
    }
}