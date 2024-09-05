<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    /**
     * Function that get informations of the database Safebase
     * @param EntityManagerInterface $entityManager
     * @return Response
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route('/', name: 'app_default')]
    public function index(EntityManagerInterface $entityManager, ManagerRegistry $doctrine): Response
    {
        $safebaseConnection = $doctrine->getConnection('default'); // Assurez-vous que 'default' est configuré pour Safebase

        // Connexion à la base de données Backup
    /*    $backupConnection = $doctrine->getConnection('backup'); // Assurez-vous qu*/
        $connection = $entityManager->getConnection();

        // Vérifier si la connexion est active
        try {
            $isConnected = $connection->connect(); // Tente de se connecter
        } catch (\Exception $e) {
            $isConnected = false; // En cas d'erreur de connexion
        }

        $params = $connection->getParams();

        $databaseName = $params['dbname'] ?? 'Nom de la base de données non défini';

        $schemaManager = $connection->getSchemaManager();
        $tables = $schemaManager->listTables();

        $columns = [];
        foreach ($tables as $table) {
            $tableName = $table->getName();
            if (!in_array($tableName, ['doctrine_migration_versions', 'messenger_messages'])) {
                $tableColumns = $schemaManager->listTableColumns($tableName);
                $columns[$tableName] = array_map(function ($column) {
                    return $column->getName();
                }, $tableColumns);
            }
        }

        $schemaTool = new SchemaTool($entityManager);
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $sql = $schemaTool->getCreateSchemaSql($metaData);

        return $this->render('default/index.html.twig', [
            'database' => $databaseName,
            'tables' => $tables,
            'isConnected' => $isConnected,
            'columns' => $columns,
/*            'backupConnection' => $backupConnection,*/
            'safebaseConnection' => $safebaseConnection,
            'params' => $params,
            'sql' => $sql,
        ]);
    }

}
