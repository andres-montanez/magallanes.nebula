<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\Type\ProjectType;
use App\Service\ProjectService;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProjectController extends AbstractController
{
    protected ProjectService $projectService;
    protected SerializerInterface $serializer;

    public function __construct(ProjectService $projectService, SerializerInterface $serializer)
    {
        $this->projectService = $projectService;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/api/projects", name="mage_api_projects", methods={"GET"})
     */
    public function apiIndex(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROJECT_LIST');

        $projects = $this->projectService->getProjects();
        return $this->json($projects, Response::HTTP_OK, [], ['list']);
    }

    /**
     * @Route("/api/projects/{id}", name="mage_api_projects_detail", methods={"GET"})
     */
    public function apiDetail(Project $project): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROJECT_EDIT');

        return $this->json($project, Response::HTTP_OK, [], ['detail']);
    }

    /**
     * @Route("/projects", name="mage_projects")
     */
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROJECT_LIST');
        return $this->render('projects/index.html.twig');
    }

    /**
     * @Route("/project/new", name="mage_project_new")
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROJECT_NEW');

        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->projectService->create($project);

            $this->addFlash('success', sprintf('Project %s created', $project->getName()));

            return $this->redirectToRoute('mage_projects');
        }

        return $this->render('projects/detail.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/project/{id}", name="mage_project_detail")
     */
    public function detail(Project $project, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PROJECT_EDIT');

        $form = $this->createForm(ProjectType::class, $project);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->projectService->update($project);

            $this->addFlash('success', sprintf('Project %s updated', $project->getName()));

            return $this->redirectToRoute('mage_projects');
        }

        return $this->render('projects/detail.html.twig', [
            'project' => $project,
            'form' => $form->createView()
        ]);
    }
}