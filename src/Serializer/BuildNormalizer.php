<?php

namespace App\Serializer;

use App\Entity\Build;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class BuildNormalizer implements ContextAwareNormalizerInterface, CacheableSupportsMethodInterface
{
    private UrlGeneratorInterface $router;

    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param Build $build
     * @return array
     */
    public function normalize($build, string $format = null, array $context = [])
    {
        $data = [
            '_id' => $build->getId(),
            'id' => $build->getId(),
            'number' => $build->getNumber(),
            'rollback_number' => $build->getRollbackNumber(),
            'created_at' => $build->getCreatedAt()->format(\DateTimeInterface::RFC2822),
            'started_at' => $build->getStartedAt() ? $build->getStartedAt()->format(\DateTimeInterface::RFC2822) : null,
            'finished_at' => $build->getFinishedAt() ? $build->getFinishedAt()->format(\DateTimeInterface::RFC2822) : null,
            'elapsed_seconds' => $build->getElapsedSeconds(),
            'status' => $build->getStatus(),
            '_href' => $this->router->generate('mage_build_detail', ['id' => $build->getId()]),
            '_href_rollback' => $this->router->generate('mage_build_rollback', ['id' => $build->getId()]),
            '_href_delete' => $this->router->generate('mage_build_delete', ['id' => $build->getId()]),
            'commit_link' => null,
        ];

        $repo = $build->getEnvironment()->getProject()->getRepository();
        if ($build->getCommitHash() && strpos($repo, 'git@github.com:') === 0) {
            $link = str_replace('git@github.com:', 'https://github.com/', $repo);
            $link = rtrim($link, '.git');
            $data['commit_link'] = sprintf('%s/commit/%s', $link, $build->getCommitHash());
        }

        if (in_array('env_builds', $context) || in_array('detail', $context)) {
            $data['branch'] = $build->getBranch();
            $data['commit_hash'] = $build->getCommitHash();
            $data['commit_short_hash'] = $build->getCommitShortHash();
            $data['commit_message'] = $build->getCommitMessage();
            $data['requested_by'] = $build->getRequestedBy();

            $data['created_at'] = $build->getCreatedAt()->format('Y-m-d H:i');
            $data['started_at_formatted'] = $build->getStartedAt() ? $build->getStartedAt()->format('Y-m-d H:i') : null;
            $data['finished_at_formatted'] = $build->getFinishedAt() ? $build->getFinishedAt()->format('Y-m-d H:i') : null;

            $data['is_successful'] = $build->isSuccessful();

        }

        if (in_array('detail', $context)) {
            $data['checkout'] = 'pending';
            $data['repository'] = 'pending';
            $data['package'] = 'pending';
            $data['release'] = 'pending';
            $data['env_vars'] = [];
            foreach ($build->getConfig()->getEnvVars() as $varName => $varValue) {
                $data['env_vars'][] = [
                    'name' => $varName,
                    'value' => $varValue
                ];
            }

            $data['stages'] = [];
            foreach ($build->getStages() as $stepCount => $stage) {
                $data['stages'][] = [
                    '_id' => $stage->getId(),
                    'id' => $stage->getId(),
                    'number' => $stepCount + 1,
                    'name' => $stage->getName(),
                    'status' => $stage->getStatus(),
                    'elapsedSeconds' => $stage->getElapsedSeconds(),
                    '_logs' => '#',
                    '_error_logs' => '#',
                ];
            }
        }

        ksort($data);
        return $data;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof Build;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}