# transformer
[![Build Status](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

## Usage
For the sake of simplicity we wont use any namespaces
and will be instantiating transformer instance manually.
Keep in mind that in real world applications 
you would use DI container for instantiating all transformer instance.

### Setup

```php
$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new TransformPipe($transformerRegistry),
]);
```

### Transformer definition 
Respecting SRP principle Transformer should always do one thing only - transform objects into serializable values
The idea of every transformer is to transform an object into scalar value representation 
that can be easily serialized

```php
class DateTimeTransformer implements CanTransform
{
    public static function getSourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $source
     * @return string
     */
    public function transform($source)
    {
        return $source->format(DateTime::ISO8601);
    }
}
```

### Registering transformer into transformer repository

```php
$dateTimeTransformer = new DateTimeTransformer();
$transformerRegistry->addTransformer($dateTimeTransformer);
```

### Using transformer
```php
$someDate = new DateTime('2000-10-10 12:00:00');
echo $transformer->transform($someDate); // outputs "2000-10-10T12:00:00+0000"
```

## Advanced usage

### Nesting
Source object properties or values returned from source accessors are not always scalar values 
in real world app but instead other objects.
In order to get only scalar values we might need to inject transformers into transformers 
to eventually have only scalar values that can be serialized.
However that is path of failure. leads to bugs if by accident .
1) Transformers gets very bloated and hard to read.
2) If currently some method returns scalar value we cannot be sure that it wont change in the future.
3) Testing becomes nightmare for transformers with high number of dependencies.

One of the purposes of transformer is that you can be sure that on successful transformation
all data returned is always scalar values that can be serialized.

 ```php
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
 
 $transformerRegistry = new TransformerRegistry();
 $transformer = new Transformer([
     new TransformPipe($transformerRegistry),
     new LazyPropertiesPipe($transformerRegistry),
 ]);
 
 $transformerRegistry->addTransformer(new DateTimeTransformer());
 $transformerRegistry->addTransformer(new DateTimeZoneTransformer());
 
 $someDate = new DateTime('2000-10-10 12:00:00', new DateTimeZone('-0400'));
 
 $data = $transformer->transform($someDate);
 var_dump($data);

// Outputs:
array(2) {
  ["iso"]=>string(24) "2000-10-10T12:00:00-0400"
  ["timezone"]=>string(6) "-04:00"
}
```

### Lazy transformation

Sometimes you do not need all the data that source object contains or refers to.
And some times some properties transformation is expensive.
e.g. requires additional database queries.
In such cases we can use AdditionalPropertiesPipe that allows us to specify transformation schema.

 ```php
 class DateTimeTransformer implements CanTransform, HasLazyProperties
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
         ];
     }
     
     public function timestamp(DateTime $source): int {
        return $source->getTimestamp();
     }
 }
 
$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new TransformPipe($transformerRegistry),
    new LazyPropertiesPipe($transformerRegistry),
]);

$someDate = new DateTime('2000-10-10 12:00:00');

$data = $transformer->transform($someDate);
var_dump($data);

// Outputs:
array(1) {
  ["iso"] => string(24) "2000-10-10T12:00:00+0000"
}

$schema = [
    'timestamp',
];
$data = $transformer->transform($someDate, new Context($schema);
var_dump($data);

//Outputs:
array(2) {
  ["iso"]=>string(24) "2000-10-10T12:00:00+0000"
  ["timestamp"]=>int(971179200)
}

 ```

### Access control
Since one of the goals of this package is to help to define clear and transparent interface
of how application data is returned starting with how it is transformed and finishing who can access it.
Since single transformer is responsible of transforming all source properties of an object issues occur
when the same transformer is being used to return data that was required by for example admin and regular use.
In order to solve this issue you can use AccessControlPipe in transformation pipeline to specify access control
for individual properties.

Example:

```php
class EvilGuard implements CanGuard {

    public function getName(): string
    {
        return self::class;
    }

    public function canAccess($source, Context $context): bool
    {
        return false;
    }
}

class DateTimeTransformer implements CanTransform, HasLazyProperties, HasAccessConfig
{
    public static function getSourceClass(): string
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
     * @param DateTime $source
     * @return array
     */
    public function transform($source): array
    {
        return [
            'iso' => $source->format(DateTime::ISO8601),
        ];
    }

    public function timestamp(DateTime $source): int {
        return $source->getTimestamp();
    }
}

$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new AccessControlPipe($transformerRegistry, [new EvilGuard()]),
    new TransformPipe($transformerRegistry),
    new LazyPropertiesPipe($transformerRegistry),
]);

$transformerRegistry->addTransformer(new DateTimeTransformer());

$someDate = new DateTime('2000-10-10 12:00:00');

$data = $transformer->transform($someDate);

var_dump($data);
// Outputs:
array(1) {
  ["iso"]=>string(24) "2000-10-10T12:00:00+0000"
}

$schema = [
    'timestamp',
];
$data = $transformer->transform($someDate, new Context($schema));

// throws Lneicelis\Transformer\Exception\AccessDeniedException
```