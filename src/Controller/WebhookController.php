<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Environment;
use App\Service\EnvironmentService;
use App\Service\ProjectService;
use App\Exception\InvalidPayloadException;
use App\Service\BuildService;
use App\Service\DeploymentService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class WebhookController extends AbstractController
{
    public function __construct(
        private EnvironmentService $environmentService,
        private ProjectService $projectService,
        private BuildService $buildService,
        private DeploymentService $deploymentService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    private function getService(): EnvironmentService
    {
        return $this->environmentService;
    }

    private function getDeploymentService(): DeploymentService
    {
        return $this->deploymentService;
    }

    #[Route('/webhook/{id}', name: 'mage_webhook', methods: ['POST'])]
    public function deploy(Request $request, string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new BadRequestException('Invalid payload.');
        }

        if (isset($payload['ref'])) {
            $build = $this->getDeploymentService()->request($environment);
            return $this->json($build, Response::HTTP_OK, [], ['groups' => ['build-request']]);
        }

        throw new BadRequestException('Invalid payload.');
    }

}
