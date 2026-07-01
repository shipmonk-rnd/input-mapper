# ShipMonk Input Mapper

Bidirectional mapper for PHP with support for generics, array shapes and nullable types. For each class, input and output mappers are generated at runtime and cached on disk. The mappers are generated only once and then reused on subsequent requests. The generated mappers are highly optimized for performance and designed to be human readable. You can see examples of generated mappers in the tests directory: [input mapper](tests/Compiler/Mapper/Object/Data/MovieMapper.php), [output mapper](tests/Compiler/Mapper/Object/Data/SimplePersonOutputMapper.php).


## Installation:

```sh
composer require shipmonk/input-mapper
```


## Features

### Built-in mappers

Input Mapper comes with built-in mappers for the following types:

* `array`, `bool`, `float`, `int`, `mixed`, `string`, `list`
* `positive-int`, `negative-int`, `int<TMin, TMax>`, `non-empty-string`, `non-empty-list`
* `array<V>`, `array<K, V>`, `list<V>`, `non-empty-list<V>`
* `array{K1: V1, ...}`
* `?T`, `Optional<T>`
* `DateTimeInterface`, `DateTimeImmutable`
* `BackedEnum`
* and most importantly classes with public constructor

All built-in mappers support both input (array → object) and output (object → array) directions.

The `int<TMin, TMax>` boundaries may be integer literals (`int<1, 10>`), the `min`/`max` keywords, or integer class constants (`int<1, Foo::MAX>`). Global constants (e.g. `int<0, PHP_INT_MAX>`) are not supported.

You can write your own mappers or replace the default mappers with your own.

### Built-in validators

Input Mapper comes with some built-in validators (input mapping only):

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
  * `AssertStringNonEmpty`
  * `AssertUrl`
* list validators:
  * `AssertListItem`
  * `AssertListLength`
  * `AssertUniqueItems` (compares items by `===`)
* date time validators:
  * `AssertDateTimeRange`

You can write your own validators if you need more.

## Usage:

### Write Input Class

To use Input Mapper, write a class with a public constructor and add either native or PHPDoc types to all constructor parameters.

Optional fields can either be marked with `#[Optional]` attribute (allowing you to specify a default value),
or if you need to distinguish between default and missing values, you can wrap the type with `ShipMonk\InputMapper\Runtime\Optional` class.

```php
use ShipMonk\InputMapper\Compiler\Attribute\Optional;

class Person
{
    public function __construct(
        public readonly string $name,

        public readonly int $age,

        #[Optional]
        public readonly ?string $email,

        /** @var list<string> */
        public readonly array $hobbies,

        /** @var list<self> */
        #[Optional(default: [])]
        public readonly array $friends,
    ) {}
}
```

By default, any extra properties are not allowed. You can change that by adding `#[AllowExtraKeys]` over the class.

### Map Input

To map input data (e.g. JSON) to objects, use `MapperProvider`:

```php
$tempDir = __DIR__ . '/temp/input-mapper'; // writable, project-local directory
$autoRefresh = true; // MUST be set to false in production
$mapperProvider = new ShipMonk\InputMapper\Runtime\MapperProvider($tempDir, $autoRefresh);
$mapper = $mapperProvider->getInputMapper(Person::class);

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
} catch (\ShipMonk\InputMapper\Runtime\Exception\MappingFailedException $e) {
    // $e->getMessage() // programmer readable error message in English
    // $e->getPath() // path of the problematic field for example ['friends', 0, 'name']
    // ...
}
```

Generated mappers are PHP files that get `include`d — point `tempDir` at a directory owned by your application, not a shared world-writable location such as the system temporary directory, so that no other local user can pre-create it and plant files that your application would then execute.

### Map Output

To convert objects back to plain arrays (e.g. for JSON serialization), use the same `MapperProvider`:

```php
$mapper = $mapperProvider->getOutputMapper(Person::class);

$data = $mapper->map($person);
// ['name' => 'John', 'age' => 30, 'email' => null, 'hobbies' => ['hiking', 'reading'], 'friends' => [...]]
```

The output mapper converts objects to arrays, enums to their backing values, `DateTimeImmutable` to formatted strings, and `Optional` properties are omitted from the output when not defined. All types supported by input mapping are also supported by output mapping.

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

### Renaming keys

If the input keys do not match the property names, you can use the `#[SourceKey]` attribute to specify the key name:

```php
use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;

class Person
{
    public function __construct(
        #[SourceKey('full_name')]
        public readonly string $name,
    ) {}
}
```

To rename keys globally — e.g. camelCase PHP properties ↔ snake_case wire keys — configure a `PropertyNameTransformer`
on the `DefaultMapperCompilerFactoryProvider`. The transform is applied in both directions (input lookup and output emission),
and `#[SourceKey]` always takes precedence over it on properties where it is set:

```php
use ShipMonk\InputMapper\Compiler\MapperFactory\DefaultMapperCompilerFactoryProvider;
use ShipMonk\InputMapper\Compiler\PropertyNameTransformer\CamelToSnakeCasePropertyNameTransformer;
use ShipMonk\InputMapper\Runtime\MapperProvider;

$provider = new MapperProvider(
    tempDir: __DIR__ . '/temp',
    mapperCompilerFactoryProvider: new DefaultMapperCompilerFactoryProvider(
        new CamelToSnakeCasePropertyNameTransformer(),
    ),
);
```

The built-in `CamelToSnakeCasePropertyNameTransformer` handles common acronym boundaries (`HTTPServer` → `http_server`,
`parseURL` → `parse_url`). Implement `PropertyNameTransformer` yourself for other conventions.

### Parsing polymorphic classes (subtypes with a common parent)

If you need to parse a hierarchy of classes, you can use the `#[Discriminator]` attribute.
(The discriminator field does not need to be mapped to a property if `#[AllowExtraKeys]` is used.)

```php
use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

#[Discriminator(
    key: 'type', // key to use for mapping
    mapping: [
        'car' => Car::class,
        'truck' => Truck::class,
    ]
)]
abstract class Vehicle {
    public function __construct(
        public readonly string $type,
    ) {}
}

class Car extends Vehicle {

    public function __construct(
        string $type,
        public readonly string $color,
    ) {
        parent::__construct($type);
    }

}

class Truck extends Vehicle {

    public function __construct(
        string $type,
        public readonly string $color,
    ) {
        parent::__construct($type);
    }

}
```

or, with enum:

```php
use ShipMonk\InputMapper\Compiler\Attribute\Discriminator;

enum VehicleType: string {
    case Car = 'car';
    case Truck = 'truck';
}

#[Discriminator(
    key: 'type', // key to use for mapping
    mapping: [
        VehicleType::Car->value => Car::class,
        VehicleType::Truck->value => Truck::class,
    ]
)]
abstract class Vehicle {
    public function __construct(
        VehicleType $type,
    ) {}
}

class Car extends Vehicle {

    public function __construct(
        VehicleType $type,
        public readonly string $color,
    ) {
        parent::__construct($type);
    }

}

class Truck extends Vehicle {

    public function __construct(
        VehicleType $type,
        public readonly string $color,
    ) {
        parent::__construct($type);
    }

}
```

### Using custom mappers

To map a class with your own hand-written mapper, implement the `Mapper` interface and register a factory for the class with `MapperProvider`:

```php
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * @implements Mapper<mixed, MyCustomClass>
 */
class MyCustomClassInputMapper implements Mapper
{
    public function map(mixed $data, array $path = []): MyCustomClass
    {
        return MyCustomClass::createFrom($data);
    }
}

$mapperProvider->registerInputFactory(MyCustomClass::class, function () {
    return new MyCustomClassInputMapper();
});
```

Use `registerOutputFactory()` the same way to customize the output direction. The factory callable receives the concrete class name, the list of generic inner mappers, and the `MapperProvider` instance, so it can delegate to other mappers if needed. A factory may also be registered for an interface or parent class — it then applies to all of its implementations.

### Customizing default mappers inferred from types

To customize how default mappers are inferred from types, you need to implement `ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory` and `MapperCompilerFactoryProvider`.

Then pass your factory provider to the `MapperProvider` — the same provider serves both the input and output directions:

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
