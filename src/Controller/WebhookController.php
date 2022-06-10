<?php

namespace App\Controller;

use App\Entity\Environment;
use App\Service\EnvironmentService;
use App\Service\DeploymentService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class WebhookController extends AbstractController
{
    public function __construct(
        private EnvironmentService $environmentService,
        private DeploymentService $deploymentService
    ) {
    }

    #[Route('/webhook/{id}', name: 'mage_webhook', methods: ['POST'])]
    public function deploy(Request $request, string $id): Response
    {
        $environment = $this->environmentService->get($id);
        if (!$environment instanceof Environment) {
            throw new NotFoundHttpException(sprintf('Environment "%s" not found for requested project.', $id));
        }

        if ($environment->getWebhook() === Environment::WEBHOOK_DISABLED) {
            throw new NotAcceptableHttpException('Webhook is disabled for this environment.');
        }

        if ($request->headers->get('X-GitHub-Event') === 'ping') {
            return new Response('Pong', Response::HTTP_OK);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            throw new BadRequestException('Invalid payload.');
        }

        if ($environment->getWebhook() === Environment::WEBHOOK_PUSH) {
            $ref = sprintf('refs/heads/%s', $environment->getBranch());
            if (isset($payload['ref']) && strtolower($ref) === strtolower(strval($payload['ref']))) {
                $build = $this->deploymentService->request($environment);
                return $this->json($build, Response::HTTP_OK, [], ['groups' => ['build-request']]);
            }
            return new Response('Skipped', Response::HTTP_OK);
        } elseif ($environment->getWebhook() === Environment::WEBHOOK_RELEASE) {
            if (isset($payload['action']) && strtolower(strval($payload['action'])) === 'released') {
                $tag = strval($payload['release']['tag_name']);
                $build = $this->deploymentService->request($environment, $tag);
                return $this->json($build, Response::HTTP_OK, [], ['groups' => ['build-request']]);
            }
        }

        throw new BadRequestException('Invalid payload.');
    }
}
