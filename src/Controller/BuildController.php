<?php

namespace App\Controller;

use App\Entity\Build;
use App\Service\BuildService;
use App\Service\DeploymentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BuildController extends AbstractController
{
    public function __construct(
        private BuildService $buildService,
        private DeploymentService $deploymentService
    ) {
    }

    #[Route('/api/build/{id}', name: 'mage_api_build_get', methods: ['GET'])]
    public function get(string $id): Response
    {
        $build = $this->buildService->get($id);
        if (!$build instanceof Build) {
            throw new NotFoundHttpException(sprintf('Build "%s" not found.', $id));
        }

        return $this->json($build, Response::HTTP_OK, [], ['groups' => ['build-detail']]);
    }

    #[Route('/api/build/{id}/logs/checkout')]
    public function checkoutLogs(Build $build): Response
    {
        $response = [
            'checkout' => [
                'stdout' => $build->getCheckoutStdOut(),
                'stderr' => $build->getCheckoutStdErr(),
            ]
        ];

        return new JsonResponse($response);
    }


    /**
     * @Route("/build-rollback/{id}", name="mage_build_rollback", methods={"POST"})
     */
    public function rollback(Build $build): Response
    {
        $this->deploymentService->requestRollback($build);
        return new JsonResponse(['ok']);
    }
}
