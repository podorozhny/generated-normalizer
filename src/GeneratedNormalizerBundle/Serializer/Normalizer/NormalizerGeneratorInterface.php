<?php

namespace Podorozhny\GeneratedNormalizer\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

interface NormalizerGeneratorInterface
{
    /**
     * @param string $objectClassName
     *
     * @return NormalizerInterface|null
     */
    public function generateNormalizer(string $objectClassName);
}
