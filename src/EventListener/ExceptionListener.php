<?php

namespace App\EventListener;

use App\Exception\InvalidPayloadException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private const INTERNAL_SERVER_ERROR = 'Internal Server Error.';

    public function __construct(private bool $debug = false)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $statusMessage = $this->debug ? $exception->getMessage() : self::INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $statusMessage = $exception->getMessage();
        }

        $payload = ['code' => $statusCode, 'message' => $statusMessage];
        if ($exception instanceof InvalidPayloadException) {
            $payload['errors'] = $exception->getErrors();
        }

        $response = new JsonResponse($payload, $statusCode);

        $event->setResponse($response);
    }
}
