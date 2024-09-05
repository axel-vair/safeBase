<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DumpController extends AbstractController
{
    #[Route('/dump', name: 'app_dump')]
    public function dump(EntityManagerInterface $entityManager): Response
    {
        return $this->dumpDatabase($entityManager);
    }

    /**
     * This function is used for dump the database.
     * We get information of the database then we get the date to version the file,
     * set the path to backup our file then launch the command to dump database
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function dumpDatabase(EntityManagerInterface $entityManager): Response
    {
        $connection = $entityManager->getConnection();
        $params = $connection->getParams();

        // Get informations of connection
        $dbName = $params['dbname'];
        $user = $params['user'];

        // Date and hour to version the file
        $dateTime = new \DateTime();
        $formattedDateTime = $dateTime->format('d-m-Y_H-i-s'); // Format: YYYY-MM-DD_HH-MM-SS

        // path file dump avec date et heure
        $dumpFile = __DIR__ . '/../../var/dump/' . $dbName . '_dump_' . $formattedDateTime . '.sql';

        // Command docker exec
        $command = sprintf(
            'docker exec -t safebase-database-1 pg_dump -U %s -h localhost -p 5432 %s > %s',
            escapeshellarg($user),
            escapeshellarg($dbName),
            escapeshellarg($dumpFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return new Response('Erreur lors du dump de la base de données: ' . implode("\n", $output), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $dumpFileName = basename($dumpFile);

        return new Response('Dump de la base de données créé avec succès : ' . $dumpFileName);
    }
}
