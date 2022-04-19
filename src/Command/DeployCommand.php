<?php

namespace App\Command;

use App\Entity\Build;
use App\Service\DeploymentService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    protected static $defaultName = 'mage:deploy';
    protected DeploymentService $deploymentService;

    public function __construct(DeploymentService $deploymentService)
    {
        $this->deploymentService = $deploymentService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $build = $this->deploymentService->getBuildToProcess();
        if ($build instanceof Build) {
            if ($build->getStatus() === Build::STATUS_PENDING) {
                $output->writeln(sprintf('Deploying build %s', $build->getId()));

                $this->deploymentService->checkout($build);
                $this->deploymentService->build($build);

                if ($build->getStatus() === Build::STATUS_FAILED) {
                    return Command::FAILURE;
                }

                $this->deploymentService->package($build);
                $this->deploymentService->release($build);
            } elseif ($build->getStatus() === Build::STATUS_ROLLBACK) {
                $output->writeln(sprintf('Rollbacking build %s', $build->getId()));

                $this->deploymentService->startRollback($build);
                $this->deploymentService->release($build);
            } elseif ($build->getStatus() === Build::STATUS_DELETE) {
                $output->writeln(sprintf('Deleting build %s', $build->getId()));

                $this->deploymentService->delete($build);
            }
        }

        return Command::SUCCESS;
    }
}
