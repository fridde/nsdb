<?php


namespace App\Utils;


use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntitySerializer extends Serializer
{

    public function __construct()
    {
        $attributeLoader = new AttributeLoader();
        $classMetadataFactory = new ClassMetadataFactory($attributeLoader);

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer($classMetadataFactory)];
        parent::__construct($normalizers, $encoders);
    }

}