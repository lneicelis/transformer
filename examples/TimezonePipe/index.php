<?php

require __DIR__ . '/../../vendor/autoload.php';

use Lneicelis\Transformer\Contract\CanTransform;
use Lneicelis\Transformer\Contract\HasTimezone;
use Lneicelis\Transformer\Pipe\TimezonePipe;
use Lneicelis\Transformer\Pipe\TransformPipe;
use Lneicelis\Transformer\Transformer;
use Lneicelis\Transformer\TransformerRegistry;
use Lneicelis\Transformer\ValueObject\Context;

class DateTimeTransformer implements CanTransform
{
    public function getResourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $resource
     * @return string
     */
    public function transform($resource): string
    {
        return $resource->format('Y-m-d H:i:s');
    }
}

class TimezoneAwareContext extends Context implements HasTimezone
{
    /** @var DateTimeZone */
    private $timezone;

    public function __construct(DateTimeZone $timezone)
    {
        parent::__construct();
        $this->timezone = $timezone;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }
}

$transformerRepository = new TransformerRegistry();
$transformer = new Transformer([
    new TimezonePipe(),
    new TransformPipe($transformerRepository),
]);

$dateTimeTransformer = new DateTimeTransformer();
$transformerRepository->addTransformer($dateTimeTransformer);

$resource = new DateTime('2000-10-10 12:00:00', new DateTimeZone('-0400'));

var_dump($dateTimeTransformer->transform($resource));

$context = new TimezoneAwareContext(new DateTimeZone('+0000'));
var_dump($transformer->transform($resource, $context));