<?php

namespace Podorozhny\GeneratedNormalizerBundle\Serializer;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Podorozhny\GeneratedNormalizerBundle\Serializer\Normalizer\NormalizerGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer as BaseSerializer;

class Serializer extends BaseSerializer
{
    /**
     * @var NormalizerGeneratorInterface
     */
    private $generator;

    /**
     * @var NormalizerInterface[]
     */
    private $generatedNormalizers = [];

    /**
     * @param array                        $normalizers
     * @param array                        $encoders
     * @param NormalizerGeneratorInterface $generator
     */
    public function __construct(
        array $normalizers = [],
        array $encoders = [],
        NormalizerGeneratorInterface $generator
    )
    {
        parent::__construct($normalizers, $encoders);

        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($data, $format = null, array $context = [])
    {
        if (is_object($data)) {
            $generatedNormalizer = $this->getGeneratedNormalizerOrNull($data);

            if ($generatedNormalizer instanceof NormalizerInterface) {
                return $generatedNormalizer->normalize($data, $format, $context);
            }
        }

        return parent::normalize($data, $format, $context);
    }

    /**
     * @param object $data
     *
     * @return NormalizerInterface|null
     */
    private function getGeneratedNormalizerOrNull($data)
    {
        $class = $data instanceof Proxy ? ClassUtils::getRealClass(get_class($data)) : get_class($data);

        if (array_key_exists($class, $this->generatedNormalizers)) {
            return $this->generatedNormalizers[$class];
        }

        $normalizer = $this->generator->generateNormalizer($class);

        if (!$normalizer instanceof NormalizerInterface) {
            return null;
        }

        if ($normalizer instanceof NormalizerAwareInterface) {
            $normalizer->setNormalizer($this);
        }

        return $this->generatedNormalizers[$class] = $normalizer;
    }
}
