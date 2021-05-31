<?php

namespace App\Library\Release\Deploy;

use App\Entity\Build;
use Symfony\Component\Process\Process;

class SCPStrategy
{
    public function delete(Build $build, array $deployOptions): void
    {
        // Deploy SSH Key
        $sshKey = tempnam('/tmp', 'mgk_');
        $sshPublicKey = sprintf('%s.pub', $sshKey);
        file_put_contents($sshKey, $build->getEnvironment()->getSSHKey());
        file_put_contents($sshPublicKey, $build->getEnvironment()->getSSHPublicKey());
        chmod($sshKey, 0600);

        $logs = [];
        $connections = [];

        // Connect & Authenticate
        foreach ($deployOptions['hosts'] as $host) {
            $port = 22;
            if (strpos($host, ':') > 0) {
                list($host, $port) = explode(':', $host, 2);
            }

            $logs[$host] = [];
            $connections[$host] = ssh2_connect($host, $port);
            ssh2_auth_pubkey_file($connections[$host], $deployOptions['user'], $sshPublicKey, $sshKey);
        }

        $releaseDirectory = sprintf('%s/%d', rtrim($deployOptions['path'], '/'), $build->getNumber());

        foreach ($deployOptions['hosts'] as $host) {
            $stream = ssh2_exec(
                $connections[$host],
                sprintf('test ! -d %s || rm -rf %s', $releaseDirectory, $releaseDirectory)
            );
            $this->saveLog($stream, $logs[$host]);
            unset($stream);
        }

        // Close connections
        foreach ($deployOptions['hosts'] as $host) {
            ssh2_disconnect($connections[$host]);
        }

        // Remove SSH Keys
        unlink($sshKey);
        unlink($sshPublicKey);
    }

    public function deploy(Build $build, array $deployOptions, string $artifactsPath): void
    {
        // Deploy SSH Key
        $sshKey = tempnam('/tmp', 'mgk_');
        $sshPublicKey = sprintf('%s.pub', $sshKey);
        file_put_contents($sshKey, $build->getEnvironment()->getSSHKey());
        file_put_contents($sshPublicKey, $build->getEnvironment()->getSSHPublicKey());
        chmod($sshKey, 0600);

        $logs = [];
        $connections = [];

        // Connect & Authenticate
        foreach ($deployOptions['hosts'] as $host) {
            $port = 22;
            if (strpos($host, ':') > 0) {
                list($host, $port) = explode(':', $host, 2);
            }

            $logs[$host] = [];
            $connections[$host] = ssh2_connect($host, $port);
            ssh2_auth_pubkey_file($connections[$host], $deployOptions['user'], $sshPublicKey, $sshKey);
        }

        // Paths
        $localPackage = sprintf('%s/%d.tar.gz', $artifactsPath, $build->getNumber());
        $packageDestination = sprintf('%s/%d.tar.gz', rtrim($deployOptions['path'], '/'), $build->getNumber());
        $releaseDirectory = sprintf('%s/%d', rtrim($deployOptions['path'], '/'), $build->getNumber());

        // Copy and unpackage
        foreach ($deployOptions['hosts'] as $host) {
            $stream = ssh2_exec(
                $connections[$host],
                sprintf('mkdir -p %s', $releaseDirectory)
            );
            $this->saveLog($stream, $logs[$host]);
            unset($stream);

            ssh2_scp_send(
                $connections[$host],
                $localPackage,
                $packageDestination
            );

            $stream = ssh2_exec(
                $connections[$host],
                sprintf('tar -xz -f %s -C %s', $packageDestination, $releaseDirectory)
            );
            $this->saveLog($stream, $logs[$host]);
            unset($stream);

            $stream = ssh2_exec(
                $connections[$host],
                sprintf('rm -f %s', $packageDestination)
            );
            $this->saveLog($stream, $logs[$host]);
            unset($stream);
        }

        // Release
        foreach ($deployOptions['hosts'] as $host) {
            $stream = ssh2_exec(
                $connections[$host],
                sprintf('cd %s ; ln -snf %d current', $deployOptions['path'], $build->getNumber())
            );

            $this->saveLog($stream, $logs[$host]);
            unset($stream);
        }

        // Close connections
        foreach ($deployOptions['hosts'] as $host) {
            ssh2_disconnect($connections[$host]);
        }

        // Remove SSH Keys
        unlink($sshKey);
        unlink($sshPublicKey);
    }

    protected function saveLog($stream, &$log): void
    {
        $streamIO = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        stream_set_blocking($streamIO, true);
        $streamERR = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($streamERR, true);
        $log[] = [
            'out' => stream_get_contents($streamIO),
            'error' => stream_get_contents($streamERR)
        ];
        fclose($streamIO);
        fclose($streamERR);
    }
}
