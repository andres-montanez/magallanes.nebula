<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Environment;
use App\Form\Type\EnvironmentType;
use App\Service\DeploymentService;
use App\Service\EnvironmentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;

class EnvironmentController extends AbstractController
{
    protected EnvironmentService $environmentService;
    protected DeploymentService $deploymentService;

    public function __construct(EnvironmentService $environmentService, DeploymentService $deploymentService)
    {
        $this->environmentService = $environmentService;
        $this->deploymentService = $deploymentService;
    }

    /**
     * @Route("/api/environments/{id}", name="mage_api_environments_detail", methods={"GET"})
     */
    public function apiDetail(Environment $environment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENVIRONMENT_EDIT');

        return $this->json($environment, Response::HTTP_OK, [], ['detail']);
    }

    /**
     * @Route("/api/environments/{id}/builds", name="mage_api_environments_builds", methods={"GET"})
     */
    public function apiBuilds(Environment $environment): Response
    {
        $this->denyAccessUnlessGranted('builds', $environment);

        $builds = $this->environmentService->getBuilds($environment);

        return $this->json($builds, Response::HTTP_OK, [], ['env_builds']);
    }

    /**
     * @Route("/environment/new/{id}", name="mage_environment_new")
     */
    public function new(Project $project, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENVIRONMENT_ADD');

        $environment = new Environment();
        $environment->setProject($project);

        $form = $this->createForm(EnvironmentType::class, $environment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->environmentService->create($environment);

            $this->addFlash('success', sprintf('Environment %s created for %s', $environment->getName(), $project->getName()));

            return $this->redirectToRoute('mage_projects');
        }

        return $this->render('environments/detail.html.twig', [
            'project' => $environment->getProject(),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/environment/{id}", name="mage_environment_detail")
     */
    public function detail(Environment $environment, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ENVIRONMENT_EDIT');

        $form = $this->createForm(EnvironmentType::class, $environment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->environmentService->update($environment);

            $this->addFlash('success', sprintf('Environment %s updated for %s', $environment->getName(), $environment->getProject()->getName()));

            return $this->redirectToRoute('mage_projects');
        }

        return $this->render('environments/detail.html.twig', [
            'project' => $environment->getProject(),
            'environment' => $environment,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/environment/{id}/builds", name="mage_environment_builds")
     */
    public function builds(Environment $environment): Response
    {
        $this->denyAccessUnlessGranted('builds', $environment);

        return $this->render('environments/builds.html.twig', [
            'project' => $environment->getProject(),
            'environment' => $environment,
        ]);
    }

    /**
     * @Route("/environment/{id}/deploy", name="mage_environment_deploy", methods={"POST"})
     */
    public function deploy(Environment $environment): Response
    {
        $this->denyAccessUnlessGranted('deploy', $environment);

        $requestedBy = null;
        if ($this->getUser() instanceof UserInterface) {
            $requestedBy = $this->getUser()->getUsername();
        }

        $build = $this->deploymentService->request($environment, null, $requestedBy);

        return new JsonResponse([
            'build_id' => $build->getId(),
            'build_number' => $build->getNumber()
        ]);
    }
}