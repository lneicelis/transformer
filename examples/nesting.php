<?php

require __DIR__ . '/../vendor/autoload.php';

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Pipe\LazyPropertiesPipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\Transformer;
use Lneicelis\Transformer\TransformerRegistry;

class DateTimeTransformer implements CanTransform
{
    public function getResourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $resource
     * @return array
     */
    public function transform($resource): array
    {
        return [
            'iso' => $resource->format(DateTime::ISO8601),
            'timezone' => $resource->getTimezone(),
        ];
    }
}

class DateTimeZoneTransformer implements CanTransform
{
    public function getResourceClass(): string
    {
        return DateTimeZone::class;
    }

    /**
     * @param DateTimeZone $resource
     * @return string
     */
    public function transform($resource): string
    {
        return $resource->getName();
    }
}

$transformerRepository = new TransformerRegistry();
$transformer = new Transformer([
    new TransformPipe($transformerRepository),
    new LazyPropertiesPipe($transformerRepository),
]);

$transformerRepository->addTransformer(new DateTimeTransformer());
$transformerRepository->addTransformer(new DateTimeZoneTransformer());

$resource = new DateTime('2000-10-10 12:00:00', new DateTimeZone('-0400'));

$data = $transformer->transform($resource);
var_dump($data);