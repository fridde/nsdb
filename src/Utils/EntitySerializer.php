<?php


namespace App\Utils;


use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntitySerializer extends Serializer
{

    public function __construct()
    {
        $annotationLoader = new AnnotationLoader(new AnnotationReader());
        $classMetadataFactory = new ClassMetadataFactory($annotationLoader);

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer($classMetadataFactory)];
        parent::__construct($normalizers, $encoders);
    }

}