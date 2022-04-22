<?php

namespace App\Service;

use App\Entity\Build;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class GitService
{
    public function checkout(Build $build, string $repositoryPath): void
    {
        // Deploy SSH Key
        $sshKey = tempnam('/tmp', 'mgk_');
        file_put_contents($sshKey, $build->getEnvironment()->getProject()->getRepositorySSHPrivateKey());
        chmod($sshKey, 0600);
        umask(0022);

        // Git SSH options
        $gitSSHOptions = ['GIT_SSH_COMMAND' => sprintf('ssh -i %s -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no', $sshKey)];

        if (file_exists($repositoryPath)) {
            $filesystem = new Filesystem();
            $filesystem->remove($repositoryPath);
        }

        // Clone repository
        mkdir($repositoryPath, 0740, true);
        $process = $this->cloneRepository($build, $repositoryPath, $gitSSHOptions);
        $build
            ->setCheckoutStdOut($process->getOutput())
            ->setCheckoutStdErr($process->getErrorOutput());

        // Remove SSH Key
        unlink($sshKey);

        if (!$process->isSuccessful()) {
            $build->setStatus(Build::STATUS_CHECKOUT_FAILED);
            return;
        }

        // Get Commit Hash
        $process = new Process(['git', 'rev-parse', 'HEAD']);
        $process
            ->setWorkingDirectory($repositoryPath)
            ->setTimeout(0);

        $process->run();
        $build
            ->appendCheckoutStdOut($process->getOutput())
            ->appendCheckoutStdErr($process->getErrorOutput())
            ->setCommitHash(trim($process->getOutput()));

        // Get Commit Message
        $process = new Process(['git', 'log', '-1', '--pretty=format:%s', $build->getCommitHash()]);
        $process
            ->setWorkingDirectory($repositoryPath)
            ->setTimeout(0);
        $process->run();
        $build
            ->appendCheckoutStdOut($process->getOutput())
            ->appendCheckoutStdErr($process->getErrorOutput())
            ->setCommitMessage(trim($process->getOutput()));
    }

    public function cleanup(string $repositoryPath): void
    {
        if (file_exists($repositoryPath)) {
            $filesystem = new Filesystem();
            $filesystem->remove($repositoryPath);
        }
    }

    protected function cloneRepository(Build $build, $repositoryPath, $gitSSHOptions): Process
    {
        $process = new Process([
            'git',
            'clone',
            '--shallow-submodules',
            '--depth',
            '1',
            '--branch',
            $build->getBranch(),
            $build->getEnvironment()->getProject()->getRepository(),
            $repositoryPath,
        ]);

        $process
            ->setEnv($gitSSHOptions)
            ->setWorkingDirectory($repositoryPath)
            ->setTimeout(0);

        $process->run();
        return $process;
    }
}
