<?php

namespace Podorozhny\GeneratedNormalizerBundle\Serializer\Normalizer;

use Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Templating\EngineInterface;

final class NormalizerGenerator implements NormalizerGeneratorInterface
{
    // TODO: rename?
    const MARKER = '__CG__';

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var ClassMetadataFactoryInterface
     */
    private $classMetadataFactory;

    /**
     * @param EngineInterface               $templating
     * @param string                        $cacheDir
     * @param ClassMetadataFactoryInterface $classMetadataFactory
     */
    public function __construct(
        EngineInterface $templating,
        string $cacheDir,
        ClassMetadataFactoryInterface $classMetadataFactory
    )
    {
        $this->templating           = $templating;
        $this->cacheDir             = $cacheDir;
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function generateNormalizer(string $objectClassName)
    {
        if (count($this->classMetadataFactory->getMetadataFor($objectClassName)->getAttributesMetadata()) === 0) {
            return null;
        }

        $fileName = $this->getFilePath($objectClassName);

        $parentDirectory = dirname($fileName);

        if (!is_dir($parentDirectory) && (false === @mkdir($parentDirectory, 0775, true))
            || !is_writable($parentDirectory)
        ) {
            throw new \Exception(sprintf('Generated normalizers directory ("%s") is not writable.', $parentDirectory));
        }

        $tmpFileName = $fileName . '.' . uniqid('', true);

        file_put_contents($tmpFileName, $this->getNormalizerCode($objectClassName));
        @chmod($tmpFileName, 0664);
        rename($tmpFileName, $fileName);

        require $fileName;

        $className = $this->getNormalizerFullClassName($objectClassName);

        $normalizer = new $className;

        if (!$normalizer instanceof NormalizerInterface) {
            throw new \RuntimeException(
                sprintf('Generated normalizer should implement "%s".', NormalizerInterface::class)
            );
        }

        return $normalizer;
    }

    /**
     * @param string $objectClassName
     *
     * @return string
     */
    private function getFilePath(string $objectClassName): string
    {
        $fileName = self::MARKER . implode('', explode('\\', $objectClassName)) . 'Normalizer';

        return sprintf('%s/serializer/generated-normalizers/%s.php', $this->cacheDir, $fileName);
    }

    /**
     * @param string $objectClassName
     *
     * @return string
     */
    private function getNormalizerNamespace(string $objectClassName): string
    {
        return sprintf('GeneratedNormalizer\\%s\\%s', self::MARKER, $this->getClassNamespace($objectClassName));
    }

    /**
     * @param string $objectClassName
     *
     * @return string
     */
    private function getNormalizerClassName(string $objectClassName): string
    {
        $parts = explode('\\', $objectClassName);

        return end($parts) . 'Normalizer';
    }

    /**
     * @param string $objectClassName
     *
     * @return string
     */
    private function getNormalizerFullClassName(string $objectClassName): string
    {
        return sprintf(
            '%s\\%s',
            $this->getNormalizerNamespace($objectClassName),
            $this->getNormalizerClassName($objectClassName)
        );
    }

    /**
     * @param string $className
     *
     * @return string
     */
    private function getClassNamespace(string $className): string
    {
        $parts = explode('\\', $className);

        return implode('\\', array_slice(explode('\\', $className), 0, count($parts) - 1));
    }

    /**
     * @param string $objectClassName
     *
     * @return string
     */
    private function getNormalizerCode(string $objectClassName): string
    {
        return $this->templating->render(
            'PodorozhnyGeneratedNormalizerBundle:normalizer:class.php.twig',
            [
                'object_class_name'      => ltrim(
                    str_replace(
                        $this->getClassNamespace($objectClassName),
                        '',
                        $objectClassName
                    ),
                    '\\'
                ),
                'object_full_class_name' => $objectClassName,
                'normalizer_namespace'   => $this->getNormalizerNamespace($objectClassName),
                'normalizer_class_name'  => $this->getNormalizerClassName($objectClassName),
                'attributes'             => $this->getObjectAttributes($objectClassName),
            ]
        );
    }

    /**
     * @param string $objectClassName
     *
     * @return array
     */
    private function getObjectAttributes(string $objectClassName): array
    {
        $metadata = $this->classMetadataFactory->getMetadataFor($objectClassName);

        $attributes = $metadata->getAttributesMetadata();

        $converter = new CamelCaseToSnakeCaseNameConverter();

        $scalarAttributes = $this->getObjectScalarAttributes($objectClassName);

        return array_map(
            function (AttributeMetadata $attributeMetadata) use ($objectClassName, $converter, $scalarAttributes) {
                return [
                    'name'        => $converter->normalize($attributeMetadata->getName()),
                    'method_name' => $this->getObjectMethodName($objectClassName, $attributeMetadata->getName()),
                    'groups'      => $attributeMetadata->getGroups(),
                    'scalar'      => in_array($attributeMetadata->getName(), $scalarAttributes, true),
                ];
            },
            $attributes
        );
    }

    /**
     * @param string $objectClassName
     * @param string $attributeName
     *
     * @return string
     */
    private function getObjectMethodName(string $objectClassName, string $attributeName): string
    {
        $reflection = new \ReflectionClass($objectClassName);

        foreach ($reflection->getMethods() as $method) {
            if (in_array($method->getName(), ['get' . ucfirst($attributeName), 'is' . ucfirst($attributeName)], true)) {
                return $method->getName();
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'No getter or isser for object "%s" attribute "%s".',
                $objectClassName,
                $attributeName
            )
        );
    }

    /**
     * @todo parsing phpdoc is not the best way to get attribute type
     *
     * @param string $objectClassName
     *
     * @return array
     */
    private function getObjectScalarAttributes(string $objectClassName): array
    {
        $reflection = new \ReflectionClass($objectClassName);

        $scalarAttributes = [];

        foreach ($reflection->getProperties() as $property) {
            foreach (['string', 'int', 'integer', 'float', 'decimal', 'bool', 'boolean'] as $type) {
                if (false !== mb_strpos($property->getDocComment(), '@var ' . $type)) {
                    $scalarAttributes[] = $property->getName();

                    continue 2;
                }
            }
        }

        return $scalarAttributes;
    }
}
