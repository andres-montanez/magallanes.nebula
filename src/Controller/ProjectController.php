<?php

namespace App\Controller;

use App\Entity\Project;
use App\Service\ProjectService;
use App\Exception\InvalidPayloadException;
use App\Service\EnvironmentService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectService $projectService,
        private EnvironmentService $environmentService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    private function getService(): ProjectService
    {
        return $this->projectService;
    }

    private function getEnvironmentService(): EnvironmentService
    {
        return $this->environmentService;
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    private function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }

    #[Route('/api/project', name: 'mage_api_project_collection', methods: ['GET'])]
    public function collection(): Response
    {
        $projects = $this->getService()->getCollection();
        return $this->json($projects, Response::HTTP_OK, [], ['groups' => ['project-list']]);
    }

    #[Route('/api/project/{id}', name: 'mage_api_project_get', methods: ['GET'])]
    public function get(string $id): Response
    {
        $project = $this->getService()->get($id);
        if ($project instanceof Project) {
            return $this->json($project, Response::HTTP_OK, [], ['groups' => ['project-detail']]);
        }

        throw new NotFoundHttpException(sprintf('Project "%s" not found.', $id));
    }

    #[Route('/api/project', name: 'mage_api_project_post', methods: ['POST'])]
    public function post(Request $request): Response
    {
        $project = new Project();
        $this->getSerializer()->deserialize(
            $request->getContent(),
            Project::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $project]
        );

        $errors = $this->getValidator()->validate($project);
        if (count($errors) > 0) {
            throw new InvalidPayloadException('Invalid payload.', $errors);
        }

        $this->getService()->create($project);
        return $this->json($project, Response::HTTP_OK, [], ['groups' => ['project-detail']]);
    }

    #[Route('/api/project/{id}', name: 'mage_api_project_patch', methods: ['PATCH'])]
    public function patch(Request $request, string $id): Response
    {
        $project = $this->getService()->get($id);
        if (!$project instanceof Project) {
            return $this->json($project, Response::HTTP_OK, [], ['groups' => ['project-detail']]);
        }

        $this->getSerializer()->deserialize(
            $request->getContent(),
            Project::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $project]
        );

        $errors = $this->getValidator()->validate($project);
        if (count($errors) > 0) {
            throw new InvalidPayloadException('Invalid payload.', $errors);
        }

        $this->getService()->update($project);
        return $this->json($project, Response::HTTP_OK, [], ['groups' => ['project-detail']]);
    }

    #[Route('/api/project/{id}/environments', name: 'mage_api_environment_collection', methods: ['GET'])]
    public function environments(string $id): Response
    {
        $project = $this->getService()->get($id);
        if (!$project instanceof Project) {
            throw new NotFoundHttpException(sprintf('Project "%s" not found.', $id));
        }

        $response = [];
        $environments = $this->getEnvironmentService()->getCollection($project);
        foreach ($environments as $environment) {
            $lastBuild = $this->getEnvironmentService()->getLastBuild($environment);
            $lastSuccessBuild = $this->getEnvironmentService()->getLastSuccessBuild($environment);
            $lastFailBuild = $this->getEnvironmentService()->getLastFailBuild($environment);

            $response[] = [
                'id' => $environment->getId(),
                'code' => $environment->getCode(),
                'name' => $environment->getName(),
                'lastBuild' => $lastBuild,
                'lastSuccess' => $lastSuccessBuild,
                'lastFailure' => $lastFailBuild,
            ];
        }

        return $this->json($response, Response::HTTP_OK, [], ['groups' => ['environment-list']]);
    }
}
