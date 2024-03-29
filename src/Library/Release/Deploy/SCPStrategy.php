<?php

namespace App\Library\Release\Deploy;

use App\Entity\Build;
use phpseclib3\Net\SSH2;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class SCPStrategy
{
    /**
     * @param array<string, mixed> $deployOptions
     */
    public function delete(Build $build, array $deployOptions): void
    {
        $sshPublicKey = PublicKeyLoader::load($build->getEnvironment()->getSSHPrivateKey());

        $logs = [];
        $sshConnections = [];

        // Connect & Authenticate
        /** @var string $host */
        foreach ($deployOptions['hosts'] as $host) {
            list($host, $port) = $this->getHostAndPort($host);

            $logs[$host] = [];
            $sshConnections[$host] = new SSH2($host, intval($port));
            $sshConnections[$host]->login($deployOptions['user'], $sshPublicKey);
        }

        $releaseDirectory = sprintf('%s/%d', rtrim($deployOptions['path'], '/'), $build->getNumber());

        /** @var string $host */
        foreach ($deployOptions['hosts'] as $host) {
            $logs[$host] = $sshConnections[$host]->exec(sprintf('test ! -d %s || rm -rf %s', $releaseDirectory, $releaseDirectory));
        }
    }

    /**
     * @param array<string, mixed> $deployOptions
     */
    public function deploy(Build $build, array $deployOptions, string $artifactsPath): void
    {
        $sshPublicKey = PublicKeyLoader::load($build->getEnvironment()->getSSHPrivateKey());

        $logs = [];
        $sshConnections = [];
        $sftpConnections = [];

        // Connect & Authenticate
        /** @var string $host */
        foreach ($deployOptions['hosts'] as $host) {
            list($host, $port) = $this->getHostAndPort($host);

            $logs[$host] = [];
            $sshConnections[$host] = new SSH2($host, intval($port));
            $sshConnections[$host]->login($deployOptions['user'], $sshPublicKey);

            $sftpConnections[$host] = new SFTP($host, intval($port));
            $sftpConnections[$host]->login($deployOptions['user'], $sshPublicKey);
        }

        // Paths
        $localPackage = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getNumber());
        $packageDestination = sprintf('%s/%d.tar.gz', rtrim($deployOptions['path'], '/'), $build->getNumber());
        $releaseDirectory = sprintf('%s/%d', rtrim($deployOptions['path'], '/'), $build->getNumber());

        // Copy and unpackage
        /** @var string $host */
        foreach ($deployOptions['hosts'] as $host) {
            $logs[$host] = $sshConnections[$host]->exec(sprintf('mkdir -p %s', $releaseDirectory));
            $logs[$host] = $sftpConnections[$host]->put($packageDestination, $localPackage, SFTP::SOURCE_LOCAL_FILE);
            $logs[$host] = $sshConnections[$host]->exec(sprintf('tar -xz -f %s -C %s', $packageDestination, $releaseDirectory));
            $logs[$host] = $sshConnections[$host]->exec(sprintf('rm -f %s', $packageDestination));
        }

        // Release
        foreach ($deployOptions['hosts'] as $host) {
            $logs[$host] = $sshConnections[$host]->exec(sprintf('cd %s ; ln -snf %d current', $deployOptions['path'], $build->getNumber()));
        }
    }

    /**
     * @return string[]
     */
    private function getHostAndPort(string $host): array
    {
        $port = 22;
        if (strpos($host, ':') > 0) {
            list($host, $port) = explode(':', $host, 2);
        }

        return [$host, $port];
    }
}
