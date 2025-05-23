<?php

declare(strict_types=1);

namespace App\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Entity normalizer
 */
class EntityNormalizer implements NormalizerInterface
{
  private readonly ObjectNormalizer $normalizer;

  /**
   * Entity normalizer
   */
  public function __construct(
    /**
     * Entity manager
     */
    private readonly EntityManagerInterface $em,
    ?ClassMetadataFactoryInterface $classMetadataFactory = null,
    ?NameConverterInterface $nameConverter = null,
    ?PropertyAccessorInterface $propertyAccessor = null,
    ?PropertyTypeExtractorInterface $propertyTypeExtractor = null
  ) {
    $this->normalizer = new ObjectNormalizer($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor);
  }

  /**
   * {@inheritDoc}
   */
  public function normalize($object, ?string $format = null, array $context = [])
  {
    return $this->normalizer->normalize($object, $format, $context);
  }

  /**
   * {@inheritDoc}
   */
  public function supportsNormalization($data, ?string $format = null, array $context = []): bool
  {
    return $this->normalizer->supportsNormalization($data, $format);
  }

  /**
   * {@inheritDoc}
   */
  public function supportsDenormalization($data, $type, $format = null): bool
  {
    return \str_starts_with((string) $type, 'App\\Entity\\') && (\is_numeric($data) || \is_string($data));
  }

  /**
   * {@inheritDoc}
   */
  public function denormalize($data, string $class, $format = null, array $context = [])
  {
    return $this->em->find($class, $data);
  }
}
