# ShipMonk Input Mapper

High performance (compile-based) input mapper for PHP with support for generics, array shapes and nullable types.

## Installation:

```sh
composer require shipmonk/input-mapper
```

## Usage:

### Write Input Class

```php
use ShipMonk\InputMapper\Runtime\Optional;

class Person
{
    /**
     * @param Optional<string>     $email
     * @param list<string>         $hobbies
     * @param Optional<list<self>> $friends
     */
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly Optional $email,
        public readonly array $hobbies,
        public readonly Optional $friends,
    ) {}
}
```

Optional fields need to be wrapped with `Optional` class which allows distinguishing between `null` and missing values.

### Map Input

```php
$tempDir = sys_get_temp_dir() . '/input-mapper';
$autoRefresh = true; // set to false in production
$mapperProvider = new ShipMonk\InputMapper\Runtime\MapperProvider($tempDir, $autoRefresh);
$mapper = $mapperProvider->get(Person::class);

try {
    $person = $mapper->map([
        'name' => 'John',
        'age' => 30,
        'hobbies' => ['hiking', 'reading'],
        'friends' => [
            [
                'name' => 'Jane',
                'age' => 28,
                'hobbies' => ['hiking', 'reading'],
            ],
            [
                'name' => 'Jack',
                'age' => 28,
                'hobbies' => ['hiking', 'reading'],
            ],
        ],
    ]);
} catch (\ShipMonk\InputMapper\Runtime\MappingFailedException $e) {
    // ...
}
```

### Using custom mappers

To map classes with your custom mapper, you need to implement `ShipMonk\InputMapper\Runtime\Mapper` interface and register it with `MapperProvider`:

```php
class MyCustomMapper implements ShipMonk\InputMapper\Runtime\Mapper
{
    public function map(mixed $data, array $path): mixed
    {
        return MyCustomClass::createFrom($data);
    }
}

$mapperProvider->registerFactory(function () {
    return new MyCustomMapper();
});
```

### Customizing default mappers inferred from types

To customize how default mappers are inferred from types, you need to implement

* `ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory` and
* `ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactoryProvider`.

Then register your factory provider with `MapperProvider`:

```php
$mapperCompilerFactoryProvider = new MyCustomMapperCompilerFactoryProvider();
$mapperProvider = new ShipMonk\InputMapper\Runtime\MapperProvider($tempDir, $autoRefresh, $mapperCompilerFactoryProvider);
```


## Contributing
- Check your code by `composer check`
- Autofix coding-style by `composer fix:cs`
- All functionality must be tested
