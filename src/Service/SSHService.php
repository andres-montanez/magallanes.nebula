<?php

namespace App\Service;

use App\Entity\Environment;
use Symfony\Component\Process\Process;

class SSHService
{
    public function runCommand(Environment $environment, array $configuration): void
    {
        $sshKey = $this->deployKey($environment);
        $sshPublicKey = $sshKey . '.pub';

        // Process Host
        $port = 22;
        if (strpos($configuration['host'], ':') > 0) {
            list($configuration['host'], $port) = explode(':', $configuration['host'], 2);
        }

        // Connect & Authenticate
        $connection = ssh2_connect($configuration['host'], $port);
        ssh2_auth_pubkey_file($connection, $configuration['user'], $sshPublicKey, $sshKey);

        $stream = ssh2_exec($connection, $configuration['cmd']);

        $streamIO = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
        stream_set_blocking($streamIO, true);
        $streamERR = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($streamERR, true);
        $logs = [
            'out' => stream_get_contents($streamIO),
            'error' => stream_get_contents($streamERR)
        ];
        fclose($streamIO);
        fclose($streamERR);

        unset($stream);

        $this->purgeKey($sshKey);
    }

    private function deployKey(Environment $environment): string
    {
        // Deploy SSH Key
        $sshKey = tempnam('/tmp', 'mgk_');
        $sshPublicKey = sprintf('%s.pub', $sshKey);
        file_put_contents($sshPublicKey, $environment->getSSHPublicKey());
        file_put_contents($sshKey, $environment->getSSHKey());
        chmod($sshKey, 0600);

        return $sshKey;
    }

    private function purgeKey(string $sshKey): void
    {
        // Remove SSH Keys
        unlink($sshKey);
        unlink($sshKey . '.pub');
    }
}