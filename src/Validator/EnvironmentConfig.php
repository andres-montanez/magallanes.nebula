<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class EnvironmentConfig extends Constraint
{
    public string $message = 'The yaml is not valid or the configuration is invalid';
}