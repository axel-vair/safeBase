<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BackupsController extends AbstractController
{
    #[Route('/backups', name: 'app_backups')]
    public function index(): Response
    {
        return $this->render('backups/index.html.twig', [
            'controller_name' => 'BackupsController',
        ]);
    }
}
