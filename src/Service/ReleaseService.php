<?php

namespace App\Service;

use App\Entity\Build;
use App\Library\Release\Deploy\SCPStrategy;
use App\Library\Tool\EnvVars;

class ReleaseService
{
    public function delete(Build $build): void
    {
        $deployOptions = $build->getConfig()->getDeploymentOptions();
        $deployStrategy = new SCPStrategy();

        // Replace vars
        $deployOptions['path'] = EnvVars::replace($deployOptions['path'], $build->getConfig()->getEnvVars());
        foreach ($deployOptions['hosts'] as &$host) {
            $host = EnvVars::replace($host, $build->getConfig()->getEnvVars());
        }

        $deployStrategy->delete($build, $deployOptions);
    }

    public function release(Build $build, string $artifactsPath): void
    {
        $deployOptions = $build->getConfig()->getDeploymentOptions();
        $deployStrategy = new SCPStrategy();

        // Replace vars
        $deployOptions['path'] = EnvVars::replace($deployOptions['path'], $build->getConfig()->getEnvVars());
        foreach ($deployOptions['hosts'] as &$host) {
            $host = EnvVars::replace($host, $build->getConfig()->getEnvVars());
        }

        $deployStrategy->deploy($build, $deployOptions, $artifactsPath);
    }
}
