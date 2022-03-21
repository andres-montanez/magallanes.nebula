<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Environment;
use App\Service\EnvironmentService;
use App\Service\ProjectService;
use App\Exception\InvalidPayloadException;
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

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    private function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    #[Route('/api/environment/{projectId}', name: 'mage_api_environment_collection', methods: ['GET'])]
    public function collection(string $projectId): Response
    {
        $project = $this->getProjectService()->get($projectId);
        if (!$project instanceof Project) {
            throw new NotFoundHttpException(sprintf('Project "%s" not found.', $projectId));
        }

        $environments = $this->getService()->getCollection($project);
        return $this->json($environments, Response::HTTP_OK, [], ['groups' => ['environment-list']]);
    }

    #[Route('/api/environment/{projectId}/{id}', name: 'mage_api_environment_get', methods: ['GET'])]
    public function get(string $projectId, string $id): Response
    {
        $project = $this->getProjectService()->get($projectId);
        if (!$project instanceof Project) {
            throw new NotFoundHttpException(sprintf('Project "%s" not found.', $projectId));
        }

        $environment = $this->getService()->get($project, $id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        return $this->json($environment, Response::HTTP_OK, [], ['groups' => ['environment-detail']]);
    }

    #[Route('/api/environment/{projectId}', name: 'mage_api_environment_post', methods: ['POST'])]
    public function post(Request $request, string $projectId): Response
    {
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
}
