<?php

namespace App\Serializer;

use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AppExceptionNormalizer implements NormalizerInterface
{
    public function normalize($exception, $format = null, array $context = [])
    {
        return [
            'content' => 'Custom exception normalizer.',
            'exception'=> [
                'code' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ],
        ];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof FlattenException;
    }
}
