<?php

namespace App\Service;

use App\Entity\Environment;
use App\Entity\Build;
use App\Library\Environment\Config;
use App\Library\Tool\EnvVars;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

final class DeploymentService
{
    public const LOCAL_HOME = '/home/magallanes';
    protected const POST_TASK_SUCCESS = 'success';
    protected const POST_TASK_FAILURE = 'failure';
    protected const NOTIFY_SUCCESS = 'success';
    protected const NOTIFY_FAILURE = 'failure';

    protected string $homeOnHost;

    protected EntityManagerInterface $entityManager;
    protected BuildService $buildService;
    protected GitService $gitService;
    protected PackageService $packageService;
    protected ReleaseService $releaseService;
    protected SSHService $SSHService;
    protected ChatterInterface $chatterService;

    public function __construct(
        $magallanesHome,
        EntityManagerInterface $entityManager,
        GitService $gitService,
        BuildService $buildService,
        PackageService $packageService,
        ReleaseService $releaseService,
        SSHService $SSHService,
        ChatterInterface $chatterService
    ) {
        $this->homeOnHost = $magallanesHome;
        $this->entityManager = $entityManager;
        $this->gitService = $gitService;
        $this->buildService = $buildService;
        $this->packageService = $packageService;
        $this->releaseService = $releaseService;
        $this->SSHService = $SSHService;
        $this->chatterService = $chatterService;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    private function getBuildService(): BuildService
    {
        return $this->buildService;
    }

    public function request(Environment $environment, ?string $branch = null, ?string $requestedBy = null): Build
    {
        if ($branch === null) {
            $branch = $environment->getBranch();
        }

        // Create Build
        $build = $this->getBuildService()->create($environment, $branch);
        $build->setRequestedBy($requestedBy);

        // Persist Build
        $this->getEntityManager()->persist($build);
        $this->getEntityManager()->flush();

        return $build;
    }

    public function getBuildToProcess(): ?Build
    {
        /** @var Build $build */
        $build = $this->entityManager->getRepository(Build::class)->findOneBy([
            'status' => [
                Build::STATUS_PENDING,
                Build::STATUS_ROLLBACK,
                Build::STATUS_DELETE,
            ]
        ]);

        return $build;
    }

    public function checkout(Build $build): void
    {
        $build
            ->setStartedAt(new \DateTimeImmutable('now'))
            ->setStatus(Build::STATUS_CHECKING_OUT);
        $this->entityManager->flush();

        try {
            $this->gitService->checkout($build, $this->getRepositoryPath($build));
            $build->setStatus(Build::STATUS_CHECKED_OUT);
        } catch (\Throwable $exception) {
            $build
                ->setStatus(Build::STATUS_FAILED)
                ->setFinishedAt(new \DateTimeImmutable('now'));
            $this->notify($build, self::NOTIFY_FAILURE);
        }

        $this->entityManager->flush();
    }

    public function build(Build $build): void
    {
        $build->setStatus(Build::STATUS_BUILDING);
        $this->entityManager->flush();

        $this->buildService->build(
            $build,
            $this->getRepositoryPath($build),
            $this->getRepositoryPathOnHost($build)
        );

        if ($build->getStatus() === Build::STATUS_FAILED) {
            $build->setFinishedAt(new \DateTimeImmutable());
            $this->entityManager->flush();
            $this->notify($build, self::NOTIFY_FAILURE);
            return;
        }

        $build->setStatus(Build::STATUS_BUILT);
        $this->entityManager->flush();

        // Cleanup Repo
        $this->gitService->cleanup($this->getRepositoryPath($build));
    }

    public function package(Build $build): void
    {
        $build->setStatus(Build::STATUS_PACKAGING);
        $this->entityManager->flush();

        $this->packageService->package(
            $build,
            $this->getRepositoryPath($build),
            $this->getArtifactsPath($build)
        );

        $build->setStatus(Build::STATUS_PACKAGED);
        $this->entityManager->flush();
    }

    public function release(Build $build): void
    {
        $build->setStatus(Build::STATUS_RELEASING);
        $this->entityManager->flush();

        $this->releaseService->release(
            $build,
            $this->getArtifactsPath($build)
        );

        // Execute Post Tasks
        $this->executePostTasks($build, self::POST_TASK_SUCCESS);

        $build
            ->setStatus(Build::STATUS_SUCCESSFUL)
            ->setFinishedAt(new \DateTimeImmutable('now'));
        $this->entityManager->flush();
        $this->notify($build, self::NOTIFY_SUCCESS);

        $this->cleanup($build->getEnvironment());
    }






    public function cleanup(Environment $environment): void
    {
        $environmentConfig = new Config($environment);
        $releasesToKeep = $environmentConfig->getReleasesToKeep();
        $buildsToKeep = $environmentConfig->getBuildsToKeep();

        // Get all successful builds from the Environment
        $successfulBuilds = $this->buildService->getBuilds($environment);

        if (count($successfulBuilds) > $releasesToKeep) {
            foreach (array_slice($successfulBuilds, $releasesToKeep) as $build) {
                $this->releaseService->delete($build);
            }
        }

        if (count($successfulBuilds) > $buildsToKeep) {
            foreach (array_slice($successfulBuilds, $buildsToKeep) as $build) {
                $this->packageService->delete($build, $this->getArtifactsPath($build));
                $this->buildService->delete($build);
            }
        }
    }

    public function requestDelete(Build $build): void
    {
        $build->setStatus(Build::STATUS_DELETE);
        $this->entityManager->flush();
    }

    public function delete(Build $build): void
    {
        $this->releaseService->delete($build);
        $this->packageService->delete($build, $this->getArtifactsPath($build));
        $this->buildService->delete($build);
    }

    public function requestRollback(Build $build, ?string $requestedBy = null): void
    {
        $this->buildService->rollback($build, $requestedBy);
    }

    public function startRollback(Build $build): void
    {
        $build
            ->setStartedAt(new \DateTimeImmutable('now'))
            ->setStatus(Build::STATUS_ROLLBACKING);
        $this->entityManager->flush();

        $this->packageService->copyBuild($build, $this->getArtifactsPath($build));
    }

    protected function getRepositoryPath(Build $build): string
    {
        return sprintf('%s/repositories/%s', self::LOCAL_HOME, $build->getId());
    }

    protected function getRepositoryPathOnHost(Build $build): string
    {
        return sprintf('%s/repositories/%s', $this->homeOnHost, $build->getId());
    }

    protected function getArtifactsPath(Build $build): string
    {
        return sprintf(
            '%s/artifacts/%s/%s',
            self::LOCAL_HOME,
            $build->getEnvironment()->getProject()->getCode(),
            $build->getEnvironment()->getCode()
        );
    }

    protected function notify(Build $build, string $type): void
    {
        $config = $build->getConfig()->getGlobalPost();
        $messageText = EnvVars::replace($config[$type]['slack']['message'], $build->getConfig()->getEnvVars());
        $message = new ChatMessage($messageText);

        //$message->options((new SlackOptions())->channel('myChannel');

        $this->chatterService->send($message);
    }

    protected function executePostTasks(Build $build, string $type): void
    {
        $tasks = $build->getConfig()->getPostTasks();
        if (isset($tasks[$type]) && is_array($tasks[$type])) {
            foreach ($tasks[$type] as $task) {
                // Process Host
                if ($task['type'] === 'ssh') {
                    $task['host'] = EnvVars::replace($task['host'], $build->getConfig()->getEnvVars());
                    $this->SSHService->runCommand($build->getEnvironment(), $task);
                }
            }
        }
    }
}
