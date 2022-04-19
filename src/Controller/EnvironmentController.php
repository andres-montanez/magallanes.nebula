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

class EnvironmentController extends AbstractController
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

    private function getProjectService(): ProjectService
    {
        return $this->projectService;
    }

    private function getBuildService(): BuildService
    {
        return $this->buildService;
    }

    private function getDeploymentService(): DeploymentService
    {
        return $this->deploymentService;
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    private function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    #[Route('/api/environment/{id}', name: 'mage_api_environment_get', methods: ['GET'])]
    public function get(string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        return $this->json($environment, Response::HTTP_OK, [], ['groups' => ['environment-detail']]);
    }

    #[Route('/api/environment/{id}/summary', name: 'mage_api_environment_get_summary', methods: ['GET'])]
    public function getSummary(string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        return $this->json($environment, Response::HTTP_OK, [], ['groups' => ['environment-list', 'environment-summary']]);
    }

    #[Route('/api/environment', name: 'mage_api_environment_post', methods: ['POST'])]
    public function post(Request $request): Response
    {
        $projectId = strval($request->query->get('projectId'));
        $project = $this->getProjectService()->get($projectId);
        if (!$project instanceof Project) {
            throw new NotFoundHttpException(sprintf('Project "%s" not found.', $projectId));
        }

        $environment = new Environment();
        $environment->setProject($project);
        $this->getSerializer()->deserialize(
            $request->getContent(),
            Environment::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $environment]
        );

        $errors = $this->getValidator()->validate($environment);
        if (count($errors) > 0) {
            throw new InvalidPayloadException('Invalid payload.', $errors);
        }

        $this->getService()->create($environment);
        return $this->json($environment, Response::HTTP_OK, [], ['groups' => ['environment-detail']]);
    }

    #[Route('/api/environment/{id}', name: 'mage_api_environment_patch', methods: ['PATCH'])]
    public function patch(Request $request, string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        $this->getSerializer()->deserialize(
            $request->getContent(),
            Environment::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $environment]
        );

        $errors = $this->getValidator()->validate($environment);
        if (count($errors) > 0) {
            throw new InvalidPayloadException('Invalid payload.', $errors);
        }

        $this->getService()->update($environment);
        return $this->json($environment, Response::HTTP_OK, [], ['groups' => ['environment-detail']]);
    }

    #[Route('/api/environment/{id}/deploy', name: 'mage_api_environment_deploy', methods: ['POST'])]
    public function deploy(Request $request, string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        $branch = null;
        $payload = json_decode($request->getContent(), true);
        if (is_array($payload) && isset($payload['branch'])) {
            $branch = (string) $payload['branch'];
        }

        $build = $this->getDeploymentService()->request($environment, $branch);

        return $this->json($build, Response::HTTP_OK, [], ['groups' => ['build-request']]);
    }

    #[Route('/api/environment/{id}/builds', name: 'mage_api_environment_builds', methods: ['GET'])]
    public function builds(string $id): Response
    {
        $environment = $this->getService()->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        $builds = $this->getBuildService()->getBuilds($environment);

        return $this->json($builds, Response::HTTP_OK, [], ['groups' => ['build-list']]);
    }
}
