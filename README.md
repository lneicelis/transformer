# transformer
[![Build Status](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/build.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/lneicelis/transformer/?branch=master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/lneicelis/transformer/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

## Goals
* Define clear, unambiguous and transparent interface between client and server
* Ensure output is always serializable (JSON, XML, YML, etc)
* Data integrity and consistent data schema across whole domain (single definition of resource transformation)
* Better separation between front-end and back-end responsibilities
* Ease of transformers testability (a transformer is always responsible for single resource transformation)
* Data security (resource property access control)
* Extendability and ability to support various use cases (data normalization, graphQL schema, etc)

## Usage
For the sake of simplicity we wont use any namespaces and will be instantiating transformer instance manually.
In real world applications you would use DI container for instantiating all transformer instance.

### Setup

```php
$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new TransformPipe($transformerRegistry),
]);
```

### Transformer definition 
Most basic transformer must define source it can transform.
Transform method should return scalar value or associative array representing resource object.

```php
class DateTimeTransformer implements CanTransform
{
    public function getReourceClass(): string
    {
        return DateTime::class;
    }

    /**
     * @param DateTime $resource
     * @return string
     */
    public function transform($resource)
    {
        return $resource->format(DateTime::ISO8601);
    }
}
```

### Registering transformer into TransformerRegistry

```php
$dateTimeTransformer = new DateTimeTransformer();
$transformerRegistry->addTransformer($dateTimeTransformer);
```

### Using transformer
```php
$resource = new DateTime('2000-10-10 12:00:00');
echo $transformer->transform($resource); 

// outputs:
"2000-10-10T12:00:00+0000"
```

## Advanced usage

### Nesting
Resource object properties or values returned from resource accessors are not always scalar values 
in real world app but instead other resources.
In order to get only scalar values we might need to inject transformers into transformers 
to eventually have only scalar values that can be serialized.
However that usually leads to increased complexity, difficult testing, limited scalability and bloated transformers.

Transformer package allows you not to worry about nested objects transformation.
As long as resources returned from transform method has a transformer registered in transformer registry
eventual result of transform method will be serializable array.

Example:
 ```php
 class DateTimeTransformer implements CanTransform
 {
     public function getReourceClass(): string
     {
         return DateTime::class;
     }
 
     /**
      * @param DateTime $resource
      * @return array
      */
     public function transform($resource)
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
     public function transform($resource)
     {
         return $resource->getName();
     }
 }
 
 $transformerRegistry = new TransformerRegistry();
 $transformer = new Transformer([
     new TransformPipe($transformerRegistry),
     new LazyPropertiesPipe($transformerRegistry),
 ]);
 
 $transformerRegistry->addTransformer(new DateTimeTransformer());
 $transformerRegistry->addTransformer(new DateTimeZoneTransformer());
 
 $resource = new DateTime('2000-10-10 12:00:00', new DateTimeZone('-0400'));
 
 $data = $transformer->transform($resource);
 var_dump($data);

// Outputs:
array(2) {
  ["iso"]=>string(24) "2000-10-10T12:00:00-0400"
  ["timezone"]=>string(6) "-04:00"
}
```

### Lazy transformation (LazyPropertiesPipe)
There are cases when you do not need all available properties all the time. e.g.:
* You need granular control of when and what properties are returned from different endpoints.
* Some properties transformation is expensive (e.g. require additional DB queries);
* You want to save bandwidth by returning only relevant properties to the front-end;

In such cases we can leverage LazyPropertiesPipe that uses schema to add properties on demand.
This can be especially useful when using tools like GraphQL 
by offloading responsibility of specifying schema to the client.

 ```php
 class DateTimeTransformer implements CanTransform, HasLazyProperties
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
         ];
     }
     
     public function timestamp(DateTime $resource): int {
        return $resource->getTimestamp();
     }
 }
 
$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new TransformPipe($transformerRegistry),
    new LazyPropertiesPipe($transformerRegistry),
]);

$resource = new DateTime('2000-10-10 12:00:00');

$data = $transformer->transform($resource);
var_dump($data);

// Outputs:
array(1) {
  ["iso"] => string(24) "2000-10-10T12:00:00+0000"
}

$schema = [
    'timestamp',
];
$data = $transformer->transform($resource, new Context($schema);
var_dump($data);

//Outputs:
array(2) {
  ["iso"]=>string(24) "2000-10-10T12:00:00+0000"
  ["timestamp"]=>int(971179200)
}

 ```

### Access control (AccessControlPipe)
Since one transformer defines transformation schema for one resource an issue occurs when 
consumer of the resource data has different data access scope.

For example, the same resource might be used by regular user and by admin user, 
however admin user should be able to access more properties of the resource that a regular user.

Keeping API flexible, transparent and still allowing client to specify schema 
requires access control mechanism at transformation level.
**AccessControlPipe** can be used in transformation pipeline to specify access control
for whole data or individual properties.

Example:
```php
class OwnerOnly implements CanGuard {

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
            'timestamp' => [OwnerOnly::class],
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

$transformerRegistry = new TransformerRegistry();
$transformer = new Transformer([
    new AccessControlPipe($transformerRegistry, [new OwnerOnly()]),
    new TransformPipe($transformerRegistry),
    new LazyPropertiesPipe($transformerRegistry),
]);

$transformerRegistry->addTransformer(new DateTimeTransformer());

$resource = new DateTime('2000-10-10 12:00:00');

$data = $transformer->transform($resource);

var_dump($data);
// Outputs:
array(1) {
  ["iso"]=>string(24) "2000-10-10T12:00:00+0000"
}

$schema = [
    'timestamp',
];
$data = $transformer->transform($resource, new Context($schema));

// throws Lneicelis\Transformer\Exception\AccessDeniedException
```