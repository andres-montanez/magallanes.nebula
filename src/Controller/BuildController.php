<?php

namespace App\Controller;

use App\Entity\Build;
use App\Service\DeploymentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

class BuildController extends AbstractController
{
    protected DeploymentService $deploymentService;

    public function __construct(DeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;
    }

    /**
     * @Route("/api/build/{id}", name="mage_api_build_detail")
     */
    public function apiDetail(Build $build): Response
    {
        $this->denyAccessUnlessGranted('view', $build);

        return $this->json($build, Response::HTTP_OK, [], ['detail']);
    }

    /**
     * @Route("/build/{id}", name="mage_build_detail")
     */
    public function detail(Build $build): Response
    {
        $this->denyAccessUnlessGranted('view', $build);

        return $this->render('builds/detail.html.twig', [
            'project' => $build->getEnvironment()->getProject(),
            'environment' => $build->getEnvironment(),
            'build' => $build
        ]);
    }

    /**
     * @Route("/build/{id}/logs/{type}", name="mage_build_logs")
     */
    public function logsCheckout(Build $build, string $type): Response
    {
        $this->denyAccessUnlessGranted('view', $build);
        $response = ['logs' => '*'];

        switch ($type) {
            case 'checkout':
                $response['logs'] = $build->getCheckoutStdOut();
                break;
            case 'checkout-error':
                $response['logs'] = $build->getCheckoutStdErr();
                break;
        }

        return new JsonResponse($response);
    }

    /**
     * @Route("/build-delete/{id}", name="mage_build_delete", methods={"POST"})
     */
    public function delete(Build $build): Response
    {
        $this->denyAccessUnlessGranted('delete', $build);

        $this->deploymentService->requestDelete($build);

        return new JsonResponse(['ok']);
    }

    /**
     * @Route("/build-rollback/{id}", name="mage_build_rollback", methods={"POST"})
     */
    public function rollback(Build $build): Response
    {
        $this->denyAccessUnlessGranted('rollback', $build);

        $requestedBy = null;
        if ($this->getUser() instanceof UserInterface) {
            $requestedBy = $this->getUser()->getUsername();
        }

        $this->deploymentService->requestRollback($build, $requestedBy);

        return new JsonResponse(['ok']);
    }
}