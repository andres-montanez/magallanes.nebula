<?php

namespace App\Library\Controller;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

trait DeserializeTrait
{
    protected function deserialize(object $entity, string $payload): void
    {
        $this->serializer->deserialize(
            $payload,
            get_class($entity),
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $entity]
        );
    }
}
