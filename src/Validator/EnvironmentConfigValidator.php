<?php

namespace App\Validator;

use App\Library\Configuration\EnvironmentConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EnvironmentConfigValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof EnvironmentConfig) {
            throw new UnexpectedTypeException($constraint, EnvironmentConfig::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Validate Configuration
        try {
            $config = Yaml::parse($value);
            $processor = new Processor();
            $environmentConfiguration = new EnvironmentConfiguration();

            $processor->processConfiguration(
                $environmentConfiguration,
                [$config]
            );
        } catch (\Exception $e) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
