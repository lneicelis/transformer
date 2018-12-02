<?php
require __DIR__ . '/../vendor/autoload.php';

use Lneicelis\Transformer\Contract\CanGuard;
use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Contract\HasAccessConfig;
use Lneicelis\Transformer\Contract\HasLazyProperties;
use Lneicelis\Transformer\Pipe\AccessControlPipe;
use Lneicelis\Transformer\Pipe\LazyPropertiesPipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\Transformer;
use Lneicelis\Transformer\TransformerRegistry;
use Lneicelis\Transformer\ValueObject\AccessConfig;
use Lneicelis\Transformer\ValueObject\Context;

class EvilGuard implements CanGuard {

    public function getName(): string
    {
        return self::class;
    }

    public function canAccess($resource, Context $context): bool
    {
        return false;
    }
}

class DateTimeTransformer implements CanTransform, HasLazyProperties, HasAccessConfig
{
    public function getResourceClass(): string
    {
        return DateTime::class;
    }

    public function getAccessConfig(): AccessConfig
    {
        return new AccessConfig([], [
            'timestamp' => [EvilGuard::class],
        ]);
    }

    /**
     * @param DateTime $resource
     * @return array
     */
    public function transform($resource): array
    {
        return [
            'iso' => $resource->format(DateTime::ISO8601),
        ];
    }

    public function timestamp(DateTime $resource): int {
        return $resource->getTimestamp();
    }
}

$transformerRepository = new TransformerRegistry();
$transformer = new Transformer([
    new AccessControlPipe($transformerRepository, [new EvilGuard()]),
    new TransformPipe($transformerRepository),
    new LazyPropertiesPipe($transformerRepository),
]);

$transformerRepository->addTransformer(new DateTimeTransformer());

$resource = new DateTime('2000-10-10 12:00:00');

$data = $transformer->transform($resource);

var_dump($data);

$schema = [
    'timestamp',
];
$data = $transformer->transform($resource, new Context($schema));
