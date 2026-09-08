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

### Custom mapping with Codecs

Codecs are the primary way to provide custom bidirectional mapping for classes that don't follow the standard public-constructor pattern. A codec converts between an intermediate type (that the library knows how to map from/to `mixed`) and your domain type.

Implement the `Codec` interface with two methods: `decode()` for input and `encode()` for output:

```php
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

class Money
{
    public function __construct(
        public readonly string $currency,
        public readonly int $cents,
    ) {}
}

/**
 * @implements Codec<array{currency: string, cents: int}, Money>
 */
class MoneyCodec implements Codec
{
    public function decode(mixed $data, array $path = []): Money
    {
        if ($data['cents'] < 0) {
            throw MappingFailedException::incorrectValue($data['cents'], [...$path, 'cents'], 'non-negative integer');
        }

        return new Money($data['currency'], $data['cents']);
    }

    public function encode(mixed $data, array $path = []): array
    {
        return ['currency' => $data->currency, 'cents' => $data->cents];
    }
}
```

Then register the codec with the mapper provider:

```php
$mapperProvider->registerCodec(new MoneyCodec());

// Now Money can be used as a field type in any mapped class
$inputMapper = $mapperProvider->getInputMapper(Money::class);
$money = $inputMapper->map(['currency' => 'USD', 'cents' => 1299]);

$outputMapper = $mapperProvider->getOutputMapper(Money::class);
$data = $outputMapper->map($money); // ['currency' => 'USD', 'cents' => 1299]
```

The library auto-compiles the `mixed → intermediate` bridge (e.g. validating that the input is `array{currency: string, cents: int}`). Your codec only handles the `intermediate → domain` conversion.

The codec's domain classes are inferred from its generic parameters (an `@implements` annotation inherited from a parent class works too). When resolving a codec for a class, the class hierarchy is walked from the most specific type to the least specific one, and at each level registered codecs take precedence over type-based mapper compiler factories (such as the built-in `DateTimeInterface` handling). Hand-written mappers registered via `registerInputFactory()` / `registerOutputFactory()` take precedence over codecs. Registering two codecs claiming the same domain class is an error, as is registering a codec whose domain type cannot be resolved to a class or interface.

Register codecs before compiling mappers: a mapper already compiled for a domain class does not observe later codec registrations — whether it is loaded in the current process or cached on disk (mappers are cached per domain class name, and with `autoRefresh` disabled the cached file is reused even if codec registrations changed, which can mean either silently skipped codec validation or a runtime `LogicException` about an unregistered codec).

#### Parameterized Codecs (Codec Factories)

For generic codecs that handle a family of classes, use `registerCodecFactory()`. The factory receives the concrete class name at runtime:

```php
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\CodecRegistry;

abstract class TypedId
{
    public function __construct(public readonly string $value) {}
}

class AccountId extends TypedId {}
class OrderId extends TypedId {}

/**
 * @template T of TypedId
 * @implements Codec<string, T>
 */
class TypedIdCodec implements Codec
{
    /** @param class-string<T> $className */
    public function __construct(private readonly string $className) {}

    public static function create(string $className, CodecRegistry $registry): self
    {
        return new self($className);
    }

    public function decode(mixed $data, array $path = []): TypedId
    {
        return new ($this->className)($data);
    }

    public function encode(mixed $data, array $path = []): string
    {
        return $data->value;
    }
}

$mapperProvider->registerCodecFactory(TypedIdCodec::class, TypedIdCodec::create(...));

// Both AccountId and OrderId are now automatically mapped via TypedIdCodec
$accountId = $mapperProvider->getInputMapper(AccountId::class)->map('acc-123');
```

#### Asymmetric Codecs

Codecs support asymmetric types — you can decode into one type and encode from a different (typically wider) type. The full signature is `Codec<DI, EI, DO, EO>` where `DI`/`EO` are intermediate types and `EI`/`DO` are domain types, with defaults `DO = EI` and `EO = DI` for the common symmetric case.

```php
use ShipMonk\InputMapper\Runtime\Codec;

/**
 * Decode: string → DateTimeImmutable
 * Encode: DateTimeInterface → string
 *
 * @implements Codec<string, DateTimeInterface, DateTimeImmutable, string>
 */
class DateTimeInterfaceCodec implements Codec
{
    public function decode(mixed $data, array $path = []): DateTimeImmutable
    {
        return new DateTimeImmutable($data);
    }

    public function encode(mixed $data, array $path = []): string
    {
        return $data->format('c');
    }
}
```

#### Codecs as attributes

Instead of registering a codec globally, you can apply a codec to a single property. Mark the codec class itself with `#[Attribute]` and use it directly:

```php
use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Attribute\MapNullable;

class EventInput
{
    public function __construct(
        #[DateTimeFormatCodec(format: 'Y-m-d')]
        public readonly DateTimeImmutable $date,

        #[MapNullable(new MapCodec(new DateTimeFormatCodec(format: 'Y-m-d\TH:i:sP')))]
        public readonly ?DateTimeImmutable $startsAt,
    ) {}
}
```

To compose a codec with other mapper attributes (like `MapNullable` above), wrap it in `MapCodec` — or let the codec class implement `MapperCompilerProvider` itself by delegating to `MapCodec`, so it can be nested directly:

```php
use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DateTimeFormatCodec implements Codec, MapperCompilerProvider
{
    // ... decode() and encode() as usual ...

    public function getInputMapperCompiler(MapperCompilerFactory $mapperCompilerFactory, array $options): MapperCompiler
    {
        return (new MapCodec($this))->getInputMapperCompiler($mapperCompilerFactory, $options);
    }

    public function getOutputMapperCompiler(MapperCompilerFactory $mapperCompilerFactory, array $options): MapperCompiler
    {
        return (new MapCodec($this))->getOutputMapperCompiler($mapperCompilerFactory, $options);
    }
}
```

Codecs used as attributes are re-instantiated inside the generated mapper, so all their constructor arguments must be scalar or null values readable from properties of the same name.

A codec attribute that wants to provide its own mapper compilers must implement the full `MapperCompilerProvider` interface (both directions, as above); a codec implementing neither provider interface is wrapped in `MapCodec` automatically. Multiple codec attributes on one promoted constructor property are chained: decoding applies them in declaration order, encoding in reverse order.

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
