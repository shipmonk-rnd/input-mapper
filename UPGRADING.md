# Upgrading Guide

## From 0.x to 1.0 (Bidirectional Mapping)

This release introduces **output mapping** (object → scalar) alongside the existing input mapping (scalar → object). This required significant architectural changes: attribute classes are now separated from compiler classes, mapper compilers are organized by direction (input/output), and the runtime supports both input and output mappers.

### Breaking Changes

#### 1. Attribute classes moved to `Compiler\Attribute` namespace

All PHP attribute classes have been moved from their original locations to `ShipMonk\InputMapper\Compiler\Attribute\`. They no longer implement `MapperCompiler` directly — instead they implement `MapperCompilerProvider`, which provides both input and output mapper compilers.

| Old class | New class |
|---|---|
| `Compiler\Mapper\Array\MapArray` | `Compiler\Attribute\MapArray` |
| `Compiler\Mapper\Array\MapArrayShape` | `Compiler\Attribute\MapArrayShape` |
| `Compiler\Mapper\Array\ArrayShapeItemMapping` | `Compiler\Attribute\ArrayShapeItemMapping` |
| `Compiler\Mapper\Scalar\MapBool` | `Compiler\Attribute\MapBool` |
| `Compiler\Mapper\Scalar\MapFloat` | `Compiler\Attribute\MapFloat` |
| `Compiler\Mapper\Scalar\MapInt` | `Compiler\Attribute\MapInt` |
| `Compiler\Mapper\Scalar\MapString` | `Compiler\Attribute\MapString` |
| `Compiler\Mapper\Mixed\MapMixed` | `Compiler\Attribute\MapMixed` |
| `Compiler\Mapper\Wrapper\MapNullable` | `Compiler\Attribute\MapNullable` |
| `Compiler\Mapper\Wrapper\MapOptional` | `Compiler\Attribute\MapOptional` |
| `Compiler\Mapper\Wrapper\MapDefaultValue` | `Compiler\Attribute\MapDefaultValue` |
| `Compiler\Mapper\Object\MapObject` | `Compiler\Attribute\MapObject` |
| `Compiler\Mapper\Object\MapEnum` | `Compiler\Attribute\MapEnum` |
| `Compiler\Mapper\Object\MapDateTimeImmutable` | `Compiler\Attribute\MapDateTimeImmutable` |
| `Compiler\Mapper\Object\MapDiscriminatedObject` | `Compiler\Attribute\MapDiscriminatedObject` |
| `Compiler\Mapper\Array\MapList` | `Compiler\Attribute\MapList` |
| `Compiler\Mapper\Object\MapDate` | `Compiler\Attribute\MapDate` |
| `Compiler\Mapper\Object\AllowExtraKeys` | `Compiler\Attribute\AllowExtraKeys` |
| `Compiler\Mapper\Object\Discriminator` | `Compiler\Attribute\Discriminator` |
| `Compiler\Mapper\Object\DelegateMapperCompiler` | `Compiler\Attribute\MapDelegate` |
| `Compiler\Mapper\Wrapper\ValidatedMapperCompiler` | `Compiler\Attribute\MapValidated` |
| `Compiler\Mapper\Object\SourceKey` | `Compiler\Attribute\SourceKey` |
| `Compiler\Mapper\Optional` | `Compiler\Attribute\Optional` |

If you used any of these as attributes on constructor parameters, update the `use` statements. The attribute names themselves are unchanged.


#### 2. Mapper compiler classes renamed and reorganized by direction

Classes that previously served as both attribute and compiler have been split. The compiler logic now lives in direction-specific classes under `Compiler\Mapper\Input\` and `Compiler\Mapper\Output\`.

| Old class | New class |
|---|---|
| `Compiler\Mapper\Array\MapArray` | `Compiler\Mapper\Input\ArrayInputMapperCompiler` |
| `Compiler\Mapper\Array\MapArrayShape` | `Compiler\Mapper\Input\ArrayShapeInputMapperCompiler` |
| `Compiler\Mapper\Array\MapList` | `Compiler\Mapper\Input\ListInputMapperCompiler` |
| `Compiler\Mapper\Scalar\MapBool` | `Compiler\Mapper\Input\BoolInputMapperCompiler` |
| `Compiler\Mapper\Scalar\MapFloat` | `Compiler\Mapper\Input\FloatInputMapperCompiler` |
| `Compiler\Mapper\Scalar\MapInt` | `Compiler\Mapper\Input\IntInputMapperCompiler` |
| `Compiler\Mapper\Scalar\MapString` | `Compiler\Mapper\Input\StringInputMapperCompiler` |
| `Compiler\Mapper\Mixed\MapMixed` | `Compiler\Mapper\PassthroughMapperCompiler` |
| `Compiler\Mapper\Wrapper\MapNullable` | `Compiler\Mapper\Input\NullableInputMapperCompiler` |
| `Compiler\Mapper\Wrapper\MapOptional` | `Compiler\Mapper\Input\OptionalInputMapperCompiler` |
| `Compiler\Mapper\Wrapper\MapDefaultValue` | `Compiler\Mapper\Input\DefaultValueInputMapperCompiler` |
| `Compiler\Mapper\Wrapper\ValidatedMapperCompiler` | `Compiler\Mapper\Input\ValidatedInputMapperCompiler` |
| `Compiler\Mapper\Wrapper\ChainMapperCompiler` | `Compiler\Mapper\Input\ChainMapperCompiler` |
| `Compiler\Mapper\Object\MapObject` | `Compiler\Mapper\Input\ObjectInputMapperCompiler` |
| `Compiler\Mapper\Object\MapEnum` | `Compiler\Mapper\Input\EnumInputMapperCompiler` |
| `Compiler\Mapper\Object\MapDateTimeImmutable` | `Compiler\Mapper\Input\DateTimeImmutableInputMapperCompiler` |
| `Compiler\Mapper\Object\MapDiscriminatedObject` | `Compiler\Mapper\Input\DiscriminatedObjectInputMapperCompiler` |
| `Compiler\Mapper\Object\DelegateMapperCompiler` | `Compiler\Mapper\AbstractDelegateMapperCompiler` (abstract base) |
| — | `Compiler\Mapper\Input\DelegateInputMapperCompiler` (new) |

**New output mapper compiler classes** (no old equivalent):
- `Compiler\Mapper\Output\ArrayOutputMapperCompiler`
- `Compiler\Mapper\Output\ArrayShapeOutputMapperCompiler`
- `Compiler\Mapper\Output\ListOutputMapperCompiler`
- `Compiler\Mapper\Output\NullableOutputMapperCompiler`
- `Compiler\Mapper\Output\OptionalOutputMapperCompiler`
- `Compiler\Mapper\Output\ObjectOutputMapperCompiler`
- `Compiler\Mapper\Output\EnumOutputMapperCompiler`
- `Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler`
- `Compiler\Mapper\Output\DelegateOutputMapperCompiler`
- `Compiler\Mapper\Output\DiscriminatedObjectOutputMapperCompiler`

#### 3. `DelegateMapperCompiler` is now abstract

The concrete `DelegateMapperCompiler` class has been renamed to `AbstractDelegateMapperCompiler` and made abstract. Use `DelegateInputMapperCompiler` or `DelegateOutputMapperCompiler` instead, depending on direction.

#### 4. `MapperCompilerFactory::create()` now returns `MapperCompilerProvider`

The return type of `MapperCompilerFactory::create()` (and `DefaultMapperCompilerFactory::create()`) changed from `MapperCompiler` to `MapperCompilerProvider`. To get the actual compiler, call `->getInputMapperCompiler()` or `->getOutputMapperCompiler()` on the result.

```php
// Before
$compiler = $factory->create($type);

// After
$provider = $factory->create($type);
$inputCompiler = $provider->getInputMapperCompiler();
$outputCompiler = $provider->getOutputMapperCompiler();
```

#### 5. `Mapper<T>` generic changed to `Mapper<I, O>`

The `Mapper` interface now has two type parameters: a contravariant input type `I` and a covariant output type `O`. The `map()` method signature changed accordingly:

```php
// Before: Mapper<T> with map(mixed $data): T
// After:  Mapper<I, O> with map(I $data): O
```

`CallbackMapper` follows the same change: `CallbackMapper<T>` → `CallbackMapper<I, O>`.

#### 6. `MapperProvider` API changes

| Old method | New method |
|---|---|
| `MapperProvider::get($className)` | `MapperProvider::getInputMapper($className)` |
| `MapperProvider::registerFactory($className, $factory)` | `MapperProvider::registerInputFactory($className, $factory)` |
| — | `MapperProvider::getOutputMapper($className)` (new) |
| — | `MapperProvider::registerOutputFactory($className, $factory)` (new) |

#### 7. New interfaces: `MapperCompilerProvider`, `InputMapperCompilerProvider`, `OutputMapperCompilerProvider`

A new interface hierarchy has been introduced:

- `InputMapperCompilerProvider` — provides `getInputMapperCompiler(): MapperCompiler`
- `OutputMapperCompilerProvider` — provides `getOutputMapperCompiler(): MapperCompiler`
- `MapperCompilerProvider extends InputMapperCompilerProvider, OutputMapperCompilerProvider` — provides both

All attribute classes now implement `MapperCompilerProvider`. If you have custom attribute classes that previously implemented `MapperCompiler`, they should now implement one of these provider interfaces instead.

### Migration Steps

1. **Update attribute imports**: Change `use` statements for all attribute classes from their old namespace to `ShipMonk\InputMapper\Compiler\Attribute\*`.

2. **Update compiler class references**: If you reference compiler classes directly (e.g. in custom factories or tests), update to the new `Input\*InputMapperCompiler` or `Output\*OutputMapperCompiler` names.

3. **Update `MapperProvider::get()` calls**: Replace `->get($class)` with `->getInputMapper($class)` for input mapping. Use `->getOutputMapper($class)` for the new output mapping.

4. **Update `MapperProvider::registerFactory()` calls**: Replace `->registerFactory(...)` with `->registerInputFactory(...)`. Use `->registerOutputFactory(...)` for output mapper factories.

5. **Update `MapperCompilerFactory` implementations**: If you implement `MapperCompilerFactory`, change `create()` to return `MapperCompilerProvider` instead of `MapperCompiler`.

6. **Update generic type annotations**: If you type-hint `Mapper<T>` anywhere, update to `Mapper<mixed, T>` (for input mappers) or `Mapper<T, mixed>` (for output mappers).

7. **Regenerate compiled mappers**: Delete the contents of your mapper temp directory and let them be recompiled. Generated input mapper class names now use `*Mapper_*` suffix, output mappers use `*OutputMapper_*` suffix.
