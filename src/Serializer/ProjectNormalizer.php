<?php

namespace App\Serializer;

use App\Entity\Project;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class ProjectNormalizer implements ContextAwareNormalizerInterface, CacheableSupportsMethodInterface
{
    private UrlGeneratorInterface $router;
    private EnvironmentNormalizer $environmentNormalizer;

    public function __construct(UrlGeneratorInterface $router, EnvironmentNormalizer $environmentNormalizer)
    {
        $this->router = $router;
        $this->environmentNormalizer = $environmentNormalizer;
    }

    /**
     * @param Project $project
     * @return array
     */
    public function normalize($project, string $format = null, array $context = [])
    {
        $data = [
            '_id' => $project->getId(),
            'id' => $project->getId(),
            'code' => $project->getCode(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            '_self' => $this->router->generate('mage_api_projects_detail', ['id' => $project->getId()]),
            '_href' => $this->router->generate('mage_project_detail', ['id' => $project->getId()]),
            '_href_new_env' => $this->router->generate('mage_environment_new', ['id' => $project->getId()]),
        ];

        if (in_array('list', $context)) {
            $data['environments'] = [];
            foreach ($project->getEnvironments() as $environment) {
                $data['environments'][] = $this->environmentNormalizer->normalize($environment, $format, $context);
            }
        } elseif (in_array('detail', $context)) {
            $data['config'] = $project->getConfig();
            $data['repository'] = $project->getRepository();
            $data['repository_ssh_key'] = $project->getRepositorySSHKey();
        }

        ksort($data);
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Project;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}