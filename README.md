# ShipMonk Input Mapper

High performance input mapper for PHP with support for generics, array shapes and nullable types. For each input class, a mapper is generated at runtime and cached on disk. The mapper is generated only once and then reused on subsequent requests. The generated mapper is highly optimized for performance and it is designed to be human readable. You can see [example of generated mappers in the tests directory](tests/Compiler/Mapper/Object/Data/PersonMapper.php).


## Installation:

```sh
composer require shipmonk/input-mapper
```


## Features

### Built-in mappers

Input Mapper comes with built-in mappers for the following types:

* `array`, `bool`, `float`, `int`, `mixed`, `string`, `list`
* `positive-int`, `negative-int`, `int<TMin, TMax>`
* `array<V>`, `array<K, V>`, `list<V>`
* `array{K1: V1, ...}`
* `?T`, `Optional<T>`
* `DateTimeInterface`, `DateTimeImmutable`
* `BackedEnum`
* and most importantly classes with public constructor

You can write your own mappers or replace the default mappers with your own.

### Built-in validators

Input Mapper comes with some built-in validators:

* int validators:
  * `AssertInt16`
  * `AssertInt32`
  * `AssertIntRange`
  * `AssertPositiveInt`
  * `AssertNegativeInt`
  * `AssertNonNegativeInt`
  * `AssertNonPositiveInt`
  * `AssertIntMultipleOf`
* float validators:
  * `AssertFloatRange`
  * `AssertPositiveFloat`
  * `AssertNegativeFloat`
  * `AssertNonNegativeFloat`
  * `AssertNonPositiveFloat`
  * `AssertFloatMultipleOf`
* string validators:
  * `AssertStringLength`
  * `AssertStringMatches`
  * `AssertUrl`
* list validators:
  * `AssertListItem`
* date time validators:
  * `AssertDateTimeRange`

You can write your own validators if you need more.

## Usage:

### Write Input Class

To use Input Mapper, write a class with a public constructor and add either native or PHPDoc types to all constructor parameters.

Optional fields need to be wrapped with the Optional class, which allows distinguishing between null and missing values.

```php
use ShipMonk\InputMapper\Runtime\Optional;

class Person
{
    public function __construct(
        public readonly string $name,
        
        public readonly int $age,
        
        /** @var Optional<string> */
        public readonly Optional $email,
        
        /** @var list<string> */
        public readonly array $hobbies,
        
        /** @var Optional<list<self>> */
        public readonly Optional $friends,
    ) {}
}
```

By default, any extra properties are not allowed. You can change that by adding `#[AllowExtraKeys]` over the class.

### Map Input

To map input, provide a path to a writable directory where generated mappers will be stored.

It's important to set $autoRefresh to false in production to avoid recompiling mappers on every request.

```php
$tempDir = sys_get_temp_dir() . '/input-mapper';
$autoRefresh = true; // MUST be set to false in production
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

### Adding Validation Rules

You can add validation rules by adding attributes to constructor parameters.

For example, to validate that `age` is between 18 and 99, you can add the `AssertIntRange` attribute to the constructor parameter:

```php
use ShipMonk\InputMapper\Compiler\Validator\Int\AssertIntRange;

class Person
{
    public function __construct(
        public readonly string $name,
        
        #[AssertIntRange(gte: 18, lte: 99)]
        public readonly int $age,
    ) {}
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

$mapperProvider->registerFactory(MyCustomClass::class, function () {
    return new MyCustomMapper();
});
```

### Customizing default mappers inferred from types

To customize how default mappers are inferred from types, you need to implement

* `ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory` and
* `ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactoryProvider`.

Then register your factory provider with `MapperProvider`:

```php
$mapperProvider = new ShipMonk\InputMapper\Runtime\MapperProvider(
    tempDir: $tempDir,
    autoRefresh: $autoRefresh,
    mapperCompilerFactoryProvider: new MyCustomMapperCompilerFactoryProvider(),
);
```


## Contributing
- Check your code by `composer check`
- Autofix coding-style by `composer fix:cs`
- All functionality must be tested
