# transformer
API layer transformation component

* install
* configure

## Usage
For the sake of simplicity we wont use any namespaces
and will be instantiating transformer instance manually.
Keep in mind that in real world applications 
you would use DI container for instantiating all transformer instance.

### 

```php
$transformerRepository = new TransformerRepository();
$transformPipe = new TransformPipe($transformerRepository);
$transformer = new Transformer([
    $transformPipe,
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
$transformerRepository->addTransformer($dateTimeTransformer);
```

### Using transformer
```php
$someDate = new DateTime('2000-10-10 12:00:00');
echo $transformer->transform($someDate); // Outouts "2000-10-10T12:00:00Z"
```


