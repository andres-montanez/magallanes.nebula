<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class HealthCheckController extends AbstractController
{
    #[Route('/health-check', name: 'mage_health_check')]
    public function healthCheck(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok'], JsonResponse::HTTP_OK);
    }
}
