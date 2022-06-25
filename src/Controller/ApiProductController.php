<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiProductController extends AbstractController
{
    #[Route('/api/product', name: 'app_api_product')]
    public function index(): Response
    {
        return $this->render('api_product/index.html.twig', [
            'controller_name' => 'ApiProductController',
        ]);
    }
}
