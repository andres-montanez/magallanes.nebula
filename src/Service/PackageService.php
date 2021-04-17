<?php

namespace App\Service;

use App\Entity\Build;
use Symfony\Component\Process\Process;

class PackageService
{
    public function delete(Build $build, string $artifactsPath): void
    {
        $tarFile = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getNumber());
        if (file_exists($tarFile)) {
            unlink($tarFile);
        }
    }

    public function package(Build $build, string $repositoryPath, string $artifactsPath): void
    {
        $buildConfig = $build->getConfig();
        $packageOptions = $buildConfig->getPackageOptions();

        if (!file_exists($artifactsPath)) {
            mkdir($artifactsPath, 0740, true);
        }

        $tarFile = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getNumber());

        // Tar parameters
        $params = [];
        $params[] = 'tar';

        if (isset($packageOptions['user'])) {
            $params[] = sprintf('--owner=%s', $packageOptions['user']);
        }

        if (isset($packageOptions['group'])) {
            $params[] = sprintf('--group=%s', $packageOptions['group']);
        }

        if (isset($packageOptions['excludes']) && is_array($packageOptions['excludes'])) {
            foreach ($packageOptions['excludes'] as $exclude) {
                $params[] = sprintf('--exclude=%s', $exclude);
            }
        }

        $params[] = '--exclude-vcs';
        $params[] = '-cz';
        $params[] = '-f';
        $params[] = $tarFile;
        $params[] = './';

        // Create Tar
        $processes = new Process($params);
        $processes
            ->setWorkingDirectory($repositoryPath)
            ->setTimeout(0)
        ;

        $processes->run();
    }

    public function copyBuild(Build $build, string $artifactsPath): void
    {
        $originTarFile = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getRollbackNumber());
        $newTarFile = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getNumber());

        copy($originTarFile, $newTarFile);
    }
}