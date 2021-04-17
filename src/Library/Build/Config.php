<?php

namespace App\Library\Build;

use App\Entity\Environment;
use App\Library\Environment\Config as EnvironmentConfig;
use App\Entity\Build;
use Symfony\Component\Yaml\Yaml;

class Config
{
    protected array $environmentConfig = [];
    protected array $envVars = [];
    protected array $dockerOptions = [];
    protected array $stages = [];
    protected array $packageOptions = [];
    protected array $deploymentOptions = [];
    protected array $postTasks = [];

    public function __construct(Build $build)
    {
        $environmentConfig = new EnvironmentConfig($build->getEnvironment());
        $this->environmentConfig = $environmentConfig->getConfig();

        $this->processEnvVars($build);
        $this->processDockerOptions($build);
        $this->processStages();
        $this->processPackageOptions($build);
        $this->processDeploymentOptions();
        $this->processPostTasks();
    }

    private function processEnvVars(Build $build): void
    {
        $projectConfig = Yaml::parse($build->getEnvironment()->getProject()->getConfig());

        // Define base Env Vars
        $this->envVars = [
            'PROJECT' => $build->getEnvironment()->getProject()->getCode(),
            'PROJECT_NAME' => $build->getEnvironment()->getProject()->getName(),
            'ENVIRONMENT' => $build->getEnvironment()->getCode(),
            'ENVIRONMENT_NAME' => $build->getEnvironment()->getName(),
            'BUILD_ID' => $build->getId(),
            'BUILD_NUMBER' => $build->getNumber(),
            'COMMIT_HASH' => $build->getCommitHash(),
            'COMMIT_SHORT_HASH' => $build->getCommitShortHash(),
        ];

        // Process Project Env Vars
        if (isset($projectConfig['env']) && is_array($projectConfig['env'])) {
            $this->envVars = array_merge($this->envVars, $projectConfig['env']);
        }

        // Process Environment Env Vars
        if (isset($this->environmentConfig['env']) && is_array($this->environmentConfig['env'])) {
            $this->envVars = array_merge($this->envVars, $this->environmentConfig['env']);
        }
    }

    private function processDockerOptions(Build $build): void
    {
        $options = [];

        $projectConfig = Yaml::parse($build->getEnvironment()->getProject()->getConfig());

        if (isset($projectConfig['docker']) && is_array($projectConfig['docker'])) {
            $options = array_merge($options, $projectConfig['docker']);
        }

        if (isset($this->environmentConfig['docker']) && is_array($this->environmentConfig['docker'])) {
            $options = array_merge($options, $this->environmentConfig['docker']);
        }

        $this->dockerOptions = $options;
    }

    private function processStages(): void
    {
        $stages = [];

        if (isset($this->environmentConfig['stages']) && is_array($this->environmentConfig['stages'])) {
            $stages = $this->environmentConfig['stages'];
        }

        $this->stages = $stages;
    }

    private function processPackageOptions(Build $build): void
    {
        $options = [];

        $projectConfig = Yaml::parse($build->getEnvironment()->getProject()->getConfig());

        if (isset($projectConfig['package']) && is_array($projectConfig['package'])) {
            $options = array_merge($options, $projectConfig['package']);
        }

        if (isset($this->environmentConfig['package']) && is_array($this->environmentConfig['package'])) {
            $options = array_merge($options, $this->environmentConfig['package']);
        }

        $this->packageOptions = $options;
    }

    private function processDeploymentOptions(): void
    {
        $options = [];

        if (isset($this->environmentConfig['release']) && is_array($this->environmentConfig['release'])) {
            if (isset($this->environmentConfig['release']['deploy']) && is_array($this->environmentConfig['release']['deploy'])) {
                $options = $this->environmentConfig['release']['deploy'];
            }
        }

        $this->deploymentOptions = $options;
    }

    private function processPostTasks(): void
    {
        $tasks = [];

        if (isset($this->environmentConfig['post']) && is_array($this->environmentConfig['post'])) {
            $tasks = $this->environmentConfig['post'];
        }

        $this->postTasks = $tasks;
    }

    public function getEnvVars(): array
    {
        return $this->envVars;
    }

    public function getDockerOptions(): array
    {
        return $this->dockerOptions;
    }

    public function getStages(): array
    {
        return $this->stages;
    }

    public function getPackageOptions(): array
    {
        return $this->packageOptions;
    }

    public function getDeploymentOptions(): array
    {
        return $this->deploymentOptions;
    }

    public function getPostTasks(): array
    {
        return $this->postTasks;
    }
}