<?php

namespace App\Twig;

use App\Entity\Build;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BuildExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('get_build_checkout_status', [$this, 'getCheckoutStatus']),
            new TwigFunction('get_build_package_status', [$this, 'getPackageStatus']),
            new TwigFunction('get_build_release_status', [$this, 'getReleaseStatus']),
            new TwigFunction('get_build_commit_link', [$this, 'getCommitLink']),
        ];
    }

    public function getCheckoutStatus(Build $build): string
    {
        switch ($build->getStatus()) {
            case Build::STATUS_PENDING:
                return 'Pending';
            case Build::STATUS_CHECKING_OUT:
                return 'In progress';
            case Build::STATUS_CHECKOUT_FAILED:
                return 'Failed';
            default:
                return 'Done';
        }
    }

    public function getPackageStatus(Build $build): string
    {
        switch ($build->getStatus()) {
            case Build::STATUS_PACKAGED:
            case Build::STATUS_RELEASING:
            case Build::STATUS_SUCCESSFUL:
                return 'Done';
            case Build::STATUS_PACKAGING:
                return 'In progress';
            case Build::STATUS_CHECKOUT_FAILED:
                return 'Failed';
            default:
                return 'Pending';
        }
    }

    public function getReleaseStatus(Build $build): string
    {
        switch ($build->getStatus()) {
            case Build::STATUS_SUCCESSFUL:
                return 'Done';
            case Build::STATUS_RELEASING:
                return 'In progress';
            case Build::STATUS_CHECKOUT_FAILED:
                return 'Failed';
            default:
                return 'Pending';
        }
    }

    public function getCommitLink(Build $build): string
    {
        $repo = $build->getEnvironment()->getProject()->getRepository();
        if (strpos($repo, 'git@github.com:') === 0) {
            $link = str_replace('git@github.com:', 'https://github.com/', $repo);
            $link = rtrim($link, '.git');
            return sprintf('%s/commit/%s', $link, $build->getCommitHash());
        }

        return '#';
    }
}