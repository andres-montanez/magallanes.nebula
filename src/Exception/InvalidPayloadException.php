<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidPayloadException extends HttpException
{
    private array $errors = [];

    /**
     * @param ConstraintViolationListInterface|array $errors
     */
    public function __construct(
        string $message = '',
        mixed $errors = null,
        \Throwable $previous = null,
        int $code = 0,
        array $headers = []
    ) {
        parent::__construct(400, $message, $previous, $headers, $code);

        if ($errors instanceof ConstraintViolationListInterface) {
            foreach ($errors as $error) {
                $this->errors[$error->getPropertyPath()] = $error->getMessage();
            }
        } elseif (is_array($errors)) {
            $this->errors = $errors;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
