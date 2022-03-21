<?php

namespace App\Controller;

use App\Entity\Project;
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

final class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectService $projectService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {
    }

    private function getService(): ProjectService
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
}
