<?php

require __DIR__ . '/../vendor/autoload.php';

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Pipe\OptionalPropertiesPipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\Transformer;
use Lneicelis\Transformer\TransformerRepository;

class DateTimeTransformer implements CanTransform
{
    public static function getSourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $source
     * @return array
     */
    public function transform($source): array
    {
        return [
            'iso' => $source->format(DateTime::ISO8601),
            'timezone' => $source->getTimezone(),
        ];
    }
}

class DateTimeZoneTransformer implements CanTransform
{
    public static function getSourceClass(): string
    {
        return DateTimeZone::class;
    }

    /**
     * @param DateTimeZone $source
     * @return string
     */
    public function transform($source): string
    {
        return $source->getName();
    }
}

$transformerRepository = new TransformerRepository();
$transformer = new Transformer([
    new TransformPipe($transformerRepository),
    new OptionalPropertiesPipe($transformerRepository),
]);

$transformerRepository->addTransformer(new DateTimeTransformer());
$transformerRepository->addTransformer(new DateTimeZoneTransformer());

$someDate = new DateTime('2000-10-10 12:00:00', new DateTimeZone('-0400'));

$data = $transformer->transform($someDate);
var_dump($data);