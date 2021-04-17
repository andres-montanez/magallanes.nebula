<?php

namespace App\Serializer;

use App\Entity\Build;
use App\Entity\Environment;
use App\Service\EnvironmentService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class EnvironmentNormalizer implements ContextAwareNormalizerInterface, CacheableSupportsMethodInterface
{
    private UrlGeneratorInterface $router;
    private EnvironmentService $environmentService;
    private BuildNormalizer $buildNormalizer;

    public function __construct(UrlGeneratorInterface $router, EnvironmentService $environmentService, BuildNormalizer $buildNormalizer)
    {
        $this->router = $router;
        $this->environmentService = $environmentService;
        $this->buildNormalizer = $buildNormalizer;
    }

    /**
     * @param Environment $environment
     * @return array
     */
    public function normalize($environment, string $format = null, array $context = [])
    {
        $data = [
            '_id' => $environment->getId(),
            'id' => $environment->getId(),
            'code' => $environment->getcode(),
            'name' => $environment->getName(),
            'branch' => $environment->getBranch(),
            '_self' => $this->router->generate('mage_api_environments_detail', ['id' => $environment->getId()]),
            '_href' => $this->router->generate('mage_environment_detail', ['id' => $environment->getId()]),
            '_href_builds' => $this->router->generate('mage_environment_builds', ['id' => $environment->getId()]),
            '_href_deploy' => $this->router->generate('mage_environment_deploy', ['id' => $environment->getId()]),
        ];

        if (in_array('list', $context)) {
            $data['is_running'] = false;
            $data['last_success'] = null;
            $lastSuccessBuild = $this->environmentService->getLastSuccessful($environment);
            if ($lastSuccessBuild instanceof Build) {
                $data['last_success'] = $this->buildNormalizer->normalize($lastSuccessBuild, $format, $context);
            }

            $data['last_failure'] = null;
            $lastFailedBuild = $this->environmentService->getLastFailed($environment);
            if ($lastFailedBuild instanceof Build) {
                $data['last_failure'] = $lastFailedBuild->getCreatedAt();
                $data['last_failure'] = $this->buildNormalizer->normalize($lastFailedBuild, $format, $context);
            }

            $data['last_duration'] = null;
            if ($lastSuccessBuild instanceof Build || $lastFailedBuild instanceof Build) {
                if ($lastSuccessBuild instanceof Build && $lastFailedBuild instanceof Build) {
                    if ($lastSuccessBuild->getCreatedAt() > $lastFailedBuild->getCreatedAt()) {
                        $data['last_duration'] = $lastSuccessBuild->getElapsedSeconds();
                    } else {
                        $data['last_duration'] = $lastFailedBuild->getElapsedSeconds();
                    }
                } elseif ($lastSuccessBuild instanceof Build) {
                    $data['last_duration'] = $lastSuccessBuild->getElapsedSeconds();
                } elseif ($lastFailedBuild instanceof Build) {
                    $data['last_duration'] = $lastFailedBuild->getElapsedSeconds();
                }
            }

            $runningBuild = $this->environmentService->getRunning($environment);
            if ($runningBuild instanceof Build) {
                $data['is_running'] = true;
                $data['_href_running_build'] = $this->router->generate('mage_build_detail', ['id' => $runningBuild->getId()]);
                $data['last_duration'] = $runningBuild->getElapsedSeconds();
            }
        } elseif (in_array('detail', $context)) {
            $data['config'] = $environment->getConfig();
            $data['ssh_key'] = $environment->getSSHKey();
        }

        ksort($data);
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Environment;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}