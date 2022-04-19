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
    public function __construct(private EntityManagerInterface $entityManager, private DockerService $dockerService)
    {
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function get(string $id): ?Build
    {
        return $this->getEntityManager()->getRepository(Build::class)->findOneBy([
            'id' => $id,
        ]);
    }

    public function create(Environment $environment, string $branch, bool $createStages = true): Build
    {
        $build = new Build();
        $build
            ->setEnvironment($environment)
            ->setNumber($this->getBuildNumber($environment))
            ->setStatus(Build::STATUS_PENDING)
            ->setBranch($branch)
            ->setCreatedAt(new \DateTimeImmutable());

        if ($createStages === true) {
            $this->createStages($build);
        }

        return $build;
    }

    private function getBuildNumber(Environment $environment): int
    {
        $sql = 'SELECT IFNULL(MAX(build_number), 0) + 1 AS new_build_number FROM build WHERE build_environment = ?';
        return intval($this->getEntityManager()->getConnection()->fetchOne($sql, [$environment->getId()]));
    }

    private function createStages(Build $build)
    {
        $buildConfig = $build->getConfig();

        // Defined Stages
        foreach ($buildConfig->getStages() as $stageConfig) {
            $stage = new BuildStage();
            $stage
                ->setBuild($build)
                ->setName($stageConfig['name'])
                ->setStatus(BuildStage::STATUS_PENDING);

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
                            ->setStatus(BuildStageStep::STATUS_PENDING);
                        $stage->addStep($step);
                    }
                }
            }

            $build->addStage($stage);
        }
    }

    /**
     * @return Build[]
     */
    public function getBuilds(Environment $environment): array
    {
        $repository = $this->entityManager->getRepository(Build::class);
        return $repository->findBy([
            'environment' => $environment,
        ], ['number' => 'DESC']);
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
            ->setRequestedBy($requestedBy);

        foreach ($build->getStages() as $stage) {
            $newStage = new BuildStage();
            $newStage
                ->setBuild($newBuild)
                ->setName($stage->getName())
                ->setStatus(BuildStage::STATUS_SUCCESSFUL);
            $newBuild->addStage($newStage);
        }

        $newBuild->setStatus(Build::STATUS_ROLLBACK);

        $this->entityManager->persist($newBuild);
        $this->entityManager->flush();
    }

    public function build(Build $build, string $repositoryPath, string $repositoryPathOnHost)
    {
        $failedStages = 0;
        $buildConfig = $build->getConfig();

        foreach ($build->getStages() as $stage) {
            $stage
                ->setStartedAt(new \DateTimeImmutable())
                ->setStatus(BuildStage::STATUS_RUNNING);
            $this->entityManager->flush();

            try {
                $failedSteps = 0;
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
                            ->setTimeout(0);

                        $process->run();
                        $step
                            ->setStdOut($process->getOutput())
                            ->setStdErr($process->getErrorOutput())
                            ->setStatus(BuildStageStep::STATUS_FAILED);

                        if ($process->isSuccessful()) {
                            $step->setStatus(BuildStageStep::STATUS_SUCCESSFUL);
                        }
                    }

                    $step->setTime(time() - $startTime);
                    if ($step->getStatus() === BuildStageStep::STATUS_FAILED) {
                        $failedSteps++;
                    }
                }

                $stage->setStatus(BuildStage::STATUS_SUCCESSFUL);
                if ($failedSteps !== 0) {
                    $failedStages++;
                    $stage->setStatus(BuildStage::STATUS_FAILED);
                }
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

        if ($failedStages !== 0) {
            $build->setStatus(Build::STATUS_FAILED);
        }
        $this->entityManager->flush();
    }
}
