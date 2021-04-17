<?php

namespace App\Service;

use App\Entity\BuildStage;
use App\Entity\BuildStageStep;
use App\Entity\Environment;
use App\Entity\Build;
use App\Library\Tool\EnvVars;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;

class BuildService
{
    protected EntityManagerInterface $entityManager;
    protected DockerService $dockerService;

    public function __construct(EntityManagerInterface $entityManager, DockerService $dockerService)
    {
        $this->entityManager = $entityManager;
        $this->dockerService = $dockerService;
    }

    /**
     * @return Build[]
     */
    public function getBuilds(Environment $environment): array
    {
        $repository = $this->entityManager->getRepository(Build::class);
        return $repository->findBy([
            'environment' => $environment,
            'status' => Build::STATUS_SUCCESSFUL
        ], ['number' => 'DESC']);
    }

    public function create(Environment $environment, string $branch, bool $createStages = true): Build
    {
        $build = new Build();
        $build
            ->setEnvironment($environment)
            ->setNumber($this->getBuildNumber($environment))
            ->setStatus(Build::STATUS_PENDING)
            ->setBranch($branch)
            ->setCreatedAt(new \DateTimeImmutable())
        ;

        if ($createStages === true) {
            $this->createStages($build);
        }

        return $build;
    }

    public function delete(Build $build): void
    {
        $this->entityManager->remove($build);
        $this->entityManager->flush();
    }

    public function rollback(Build $build, ?string $requestedBy = null): void
    {
        $newBuild = $this->create($build->getEnvironment(), $build->getBranch(), false);
        $newBuild
            ->setCommitHash($build->getCommitHash())
            ->setCommitMessage($build->getCommitMessage())
            ->setRollbackNumber($build->getNumber())
            ->setRequestedBy($requestedBy)
        ;

        foreach ($build->getStages() as $stage) {
            $newStage = new BuildStage();
            $newStage
                ->setBuild($newBuild)
                ->setName($stage->getName())
                ->setStatus(BuildStage::STATUS_SUCCESSFUL)
            ;
            $newBuild->addStage($newStage);
        }

        $newBuild->setStatus(Build::STATUS_ROLLBACK);

        $this->entityManager->persist($newBuild);
        $this->entityManager->flush();
    }

    public function build(Build $build, string $repositoryPath, string $repositoryPathOnHost)
    {
        $buildConfig = $build->getConfig();

        foreach ($build->getStages() as $stage) {
            $stage
                ->setStartedAt(new \DateTimeImmutable())
                ->setStatus(BuildStage::STATUS_RUNNING)
            ;
            $this->entityManager->flush();

            try {
                foreach ($stage->getSteps() as $step) {
                    $startTime = time();
                    if ($stage->getDocker()) {
                        $step->setStatus(BuildStageStep::STATUS_RUNNING);
                        $this->entityManager->flush();
                        $this->dockerService->run(
                            $step,
                            $buildConfig->getEnvVars(),
                            $repositoryPathOnHost,
                            $buildConfig->getDockerOptions()
                        );
                    } else {
                        $process = Process::fromShellCommandline($step->getDefinition());
                        $process
                            ->setEnv($buildConfig->getEnvVars())
                            ->setWorkingDirectory($repositoryPath)
                            ->setTimeout(0)
                        ;

                        $process->run();
                        $step
                            ->setStdOut($process->getOutput())
                            ->setStdErr($process->getErrorOutput())
                            ->setStatus(BuildStageStep::STATUS_FAILED)
                        ;

                        if ($process->isSuccessful()) {
                            $step->setStatus(BuildStageStep::STATUS_SUCCESSFUL);
                        }
                    }
                    $step->setTime(time() - $startTime);
                }
                $stage->setStatus(BuildStage::STATUS_SUCCESSFUL);
            } catch (\Exception $e) {
                $stage->setStatus(BuildStage::STATUS_FAILED);
                if (isset($step) && $step instanceof BuildStageStep) {
                    $step->setStatus(BuildStageStep::STATUS_FAILED);
                }
            } finally {
                $stage->setFinishedAt(new \DateTimeImmutable());
                $this->entityManager->flush();
            }
        }
    }

    protected function createStages(Build $build)
    {
        $buildConfig = $build->getConfig();

        // Defined Stages
        foreach ($buildConfig->getStages() as $stageConfig) {
            $stage = new BuildStage();
            $stage
                ->setBuild($build)
                ->setName($stageConfig['name'])
                ->setStatus(BuildStage::STATUS_PENDING)
            ;

            if (isset($stageConfig['docker'])) {
                $stage->setDocker(EnvVars::replace($stageConfig['docker'], $build->getConfig()->getEnvVars()));
            }

            if (isset($stageConfig['steps']) && is_array($stageConfig['steps'])) {
                foreach ($stageConfig['steps'] as $stepConfig) {
                    if (isset($stepConfig[BuildStageStep::TYPE_CMD])) {
                        $step = new BuildStageStep();
                        $step
                            ->setStage($stage)
                            ->setType(BuildStageStep::TYPE_CMD)
                            ->setDefinition($stepConfig[BuildStageStep::TYPE_CMD])
                            ->setStatus(BuildStageStep::STATUS_PENDING)
                        ;
                        $stage->addStep($step);
                    }
                }
            }

            $build->addStage($stage);
        }
    }

    protected function getBuildNumber(Environment $environment): int
    {
        $sql = 'SELECT IFNULL(MAX(build_number), 0) + 1 AS new_build_number FROM build WHERE build_environment = ?';
        return (int) $this->entityManager->getConnection()->fetchColumn($sql, [$environment->getId()]);
    }
}