<?php

namespace App\Service;

use App\Entity\Environment;
use App\Entity\Project;
use App\Library\SSH\Key;
use Symfony\Component\Process\Process;

final class SSHService
{
    public function generateEnvironmentKey(Environment $environment): Key
    {
        return $this->generateKey(sprintf('%s-%s', $environment->getCode(), $environment->getProject()->getCode()));
    }

    public function generateProjectKey(Project $project): Key
    {
        return $this->generateKey($project->getCode());
    }

    private function generateKey(string $owner): Key
    {
        $config = array(
            'digest_alg' => 'sha512',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $key = openssl_pkey_new($config);
        openssl_pkey_export($key, $private);
        $public = $this->sshEncodePublicKey(openssl_pkey_get_private($key));

        $publicWithComments = sprintf(
            '%s %s@magallanes.nebula',
            $public,
            $owner,
        );

        return new Key($private, $publicWithComments);
    }

    private function sshEncodePublicKey($privKey)
    {
        $keyInfo = openssl_pkey_get_details($privKey);

        $buffer  = sprintf(
            '%sssh-rsa%s%s',
            pack('N', 7),
            $this->sshEncodeBuffer($keyInfo['rsa']['e']),
            $this->sshEncodeBuffer($keyInfo['rsa']['n'])
        );

        return 'ssh-rsa ' . base64_encode($buffer);
    }

    private function sshEncodeBuffer($buffer)
    {
        $len = strlen($buffer);
        if (ord($buffer[0]) & 0x80) {
            $len++;
            $buffer = "\x00" . $buffer;
        }

        return pack('Na*', $len, $buffer);
    }

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
