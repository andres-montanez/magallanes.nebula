<?php

namespace App\Service;

use App\Entity\BuildStageStep;
use App\Library\Tool\EnvVars;
use Symfony\Component\HttpClient\CurlHttpClient;
class DockerService
{
    protected string $socket = '/var/run/docker.sock';

    public function run(BuildStageStep $step, array $envVars, string $directory, ?array $options = [])
    {
        $command = EnvVars::replace($step->getDefinition(), $envVars);
        $image = EnvVars::replace($step->getStage()->getDocker(), $envVars);
        $client = new CurlHttpClient(['bindto' => $this->socket]);

        // Pull image
        $response = $client->request(
            'POST',
            $this->getUrl('/images/create?fromImage=' . $image),
            [
                'timeout' => 300
            ]
        );
        $pullLog = $response->getContent(false);

        // Prepare options
        $name = sprintf('mage_%d', time());
        $env = [
            'TERM=vt220'
        ];
        foreach ($envVars as $key => $value) {
            $env[] = sprintf('%s=%s', $key, $value);
        }

        $payload = [
            'User' => 'root',
            'Env' => $env,
            'Entrypoint' => [
                '/bin/sh', '-c'
            ],
            'HostConfig' => [
                'Mounts' => [
                    [
                        'Target' => '/home/app/current',
                        'Source' => $directory,
                        'Type' => 'bind',
                        'ReadOnly' => false,
                    ]
                ],
                'Memory' => $this->getMemory($options)
            ],
            'WorkingDir' => '/home/app/current',
            'Cmd' => $command,
            'Image' => $image,
            'AttachStdout' => false,
            'AttachStderr' => false
        ];

        // Create container
        $response = $client->request(
            'POST',
            $this->getUrl('/containers/create?name=' . $name),
            [
                'body' => json_encode($payload),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($payload))
                ]
            ]
        );

        $response = json_decode($response->getContent(false), true);
        $containerId =  $response['Id'];

        // Start container
        $response = $client->request('POST', $this->getUrl(sprintf('/containers/%s/start', $containerId)));
        $response = json_decode($response->getContent(false), true);

        do {
            sleep(1);
            // Get container status
            $response = $client->request('GET', $this->getUrl(sprintf('/containers/%s/json', $containerId)));
            $response = json_decode($response->getContent(false), true);
        } while ($response['State']['Status'] === 'running');

        $step->setStatus(BuildStageStep::STATUS_FAILED);
        if ($response['State']['ExitCode'] === 0) {
            $step->setStatus(BuildStageStep::STATUS_SUCCESSFUL);
        }

        // Get container logs -- standard output
        $response = $client->request('GET', $this->getUrl(sprintf('/containers/%s/logs?stdout=true', $containerId)));
        $response = $response->getContent(false);
        $step->setStdOut(trim($response));

        // Get container logs -- standard error
        $response = $client->request('GET', $this->getUrl(sprintf('/containers/%s/logs?stderr=true', $containerId)));
        $response = $response->getContent(false);
        $step->setStdErr(trim($response));

        // Stop container
        $response = $client->request('POST', $this->getUrl(sprintf('/containers/%s/stop', $containerId)));
        $response = json_decode($response->getContent(false), true);

        // Remove container
        $response = $client->request('DELETE', $this->getUrl(sprintf('/containers/%s?force=true', $containerId)));
        $response = json_decode($response->getContent(false), true);
    }

    protected function getMemory(array $options)
    {
        if (isset($options['memory']) && is_numeric($options['memory'])) {
            return ((int) $options['memory'] * 1024 * 1024);
        }

        return 1024 * 1024 * 1024;
    }

    protected function getUrl($uri): string
    {
        return sprintf('http://docker%s', $uri);
    }
}
