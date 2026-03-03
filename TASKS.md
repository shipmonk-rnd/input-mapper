# Output Mapper — Implementation Tasks

Detailed implementation plan derived from [PLAN.md](./PLAN.md). Each phase is a self-contained unit that can be merged independently. All phases maintain passing tests at every step.

---

## Phase 1: Move Attributes to `src/Compiler/Attribute/` (BC break)

Pure mechanical refactoring — no behavioral changes.

### 1.1 Create `src/Compiler/Attribute/` directory

### 1.2 Move all attribute classes to new location

Move each file and update its namespace from `ShipMonk\InputMapper\Compiler\Mapper\{Scalar,Array,Object,Wrapper,Mixed}\` to `ShipMonk\InputMapper\Compiler\Attribute\`:

**From `Scalar/`:**
- `MapInt.php`
- `MapString.php`
- `MapBool.php`
- `MapFloat.php`

**From `Array/`:**
- `MapArray.php`
- `MapArrayShape.php`
- `MapList.php`
- `ArrayShapeItemMapping.php` (used by `MapArrayShape`, stays as value object alongside it)

**From `Object/`:**
- `MapObject.php`
- `MapEnum.php`
- `MapDate.php`
- `MapDateTimeImmutable.php`
- `MapDiscriminatedObject.php`
- `SourceKey.php`
- `AllowExtraKeys.php`
- `Discriminator.php`

**From `Wrapper/`:**
- `MapNullable.php`
- `MapOptional.php`
- `MapDefaultValue.php`
- `ValidatedMapperCompiler.php`
- `ChainMapperCompiler.php`

**From `Mixed/`:**
- `MapMixed.php`

**From `Mapper/`:**
- `Optional.php` (the `#[Optional]` attribute, not the `Runtime\Optional` interface)

### 1.3 Update all internal references

- All `use` statements across `src/` and `tests/` that reference moved classes
- `DefaultMapperCompilerFactory` attribute discovery
- PHPStan config/extensions if they reference these classes
- Autoload mappings if needed (composer.json PSR-4 should cover it since root namespace is unchanged)

### 1.4 Keep non-attribute classes in place

These stay in `src/Compiler/Mapper/`:
- `MapperCompiler.php`
- `GenericMapperCompiler.php`
- `UndefinedAwareMapperCompiler.php`
- `MapRuntime.php`

These stay in `src/Compiler/Mapper/Object/`:
- `DelegateMapperCompiler.php` (this is a compiler, not an attribute)

### 1.5 Run full test suite — all tests must pass

### 1.6 Run static analysis (PHPStan) — must pass

---

## Phase 2: Attribute Refactoring — Separate Configuration from Code Generation (BC break)

Attributes stop implementing `MapperCompiler` directly. Instead they implement provider interfaces that return `MapperCompiler` instances.

### 2.1 Create provider interfaces

Create in `src/Compiler/Attribute/`:
- `InputMapperCompilerProvider.php` — interface with `getInputMapperCompiler(): MapperCompiler`
- `OutputMapperCompilerProvider.php` — interface with `getOutputMapperCompiler(): MapperCompiler`

### 2.2 Extract input compiler classes

For each attribute that currently implements `MapperCompiler`, extract the `compile()`, `getInputType()`, `getOutputType()` logic into a new `{Kind}InputMapperCompiler` class under `src/Compiler/Mapper/Input/`:

| Attribute | New extracted class |
|-----------|-------------------|
| `MapInt` | `IntInputMapperCompiler` |
| `MapString` | `StringInputMapperCompiler` |
| `MapBool` | `BoolInputMapperCompiler` |
| `MapFloat` | `FloatInputMapperCompiler` |
| `MapMixed` | `MixedInputMapperCompiler` |
| `MapList` | `ListInputMapperCompiler` |
| `MapArray` | `ArrayInputMapperCompiler` |
| `MapArrayShape` | `ArrayShapeInputMapperCompiler` |
| `MapObject` | `ObjectInputMapperCompiler` |
| `MapEnum` | `EnumInputMapperCompiler` |
| `MapDate` | `DateInputMapperCompiler` |
| `MapDateTimeImmutable` | `DateTimeImmutableInputMapperCompiler` |
| `MapDiscriminatedObject` | `DiscriminatedObjectInputMapperCompiler` |
| `MapNullable` | `NullableInputMapperCompiler` |
| `MapOptional` | `OptionalInputMapperCompiler` |
| `MapDefaultValue` | `DefaultValueInputMapperCompiler` |
| `ValidatedMapperCompiler` | `ValidatedInputMapperCompiler` |

Each extracted class:
- Implements `MapperCompiler` (or `GenericMapperCompiler` / `UndefinedAwareMapperCompiler` where applicable)
- Contains the `compile()`, `getInputType()`, `getOutputType()` methods from the old attribute
- Accepts constructor parameters that were previously attribute properties

### 2.3 Refactor `DelegateMapperCompiler`

- Rename to `DelegateInputMapperCompiler`
- Move to `src/Compiler/Mapper/Input/`
- Keep the same logic (Mode 1: template param reference, Mode 2: concrete class reference)
- Update all references

### 2.4 Move `ChainMapperCompiler`

- Move to `src/Compiler/Mapper/Input/` (it's direction-agnostic but currently only used for input)
- Can also be used by output direction later

### 2.5 Make each attribute implement `InputMapperCompilerProvider`

Each attribute's class body changes from:
```php
class MapInt implements MapperCompiler {
    public function compile(...) { ... }
    public function getInputType() { ... }
    public function getOutputType() { ... }
}
```
To:
```php
class MapInt implements InputMapperCompilerProvider {
    public function getInputMapperCompiler(): MapperCompiler {
        return new IntInputMapperCompiler();
    }
}
```

### 2.6 Update `DefaultMapperCompilerFactory`

Change attribute discovery from:
```php
$attributes = $reflection->getAttributes(MapperCompiler::class, ReflectionAttribute::IS_INSTANCEOF);
```
To:
```php
$attributes = $reflection->getAttributes(InputMapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF);
```

Then call `->getInputMapperCompiler()` on the attribute instance.

### 2.7 Update test expectations

- Tests that assert on compiler class names need updating (e.g., `MapInt` → `IntInputMapperCompiler`)
- Generated mapper class `@see` annotations change from `@see MapInt` to `@see IntInputMapperCompiler`
- Snapshot/golden file tests may need regeneration

### 2.8 Run full test suite — all tests must pass

### 2.9 Run static analysis (PHPStan) — must pass

---

## Phase 3: Rename Core Runtime Interfaces (BC break)

### 3.1 Rename runtime classes

| Old name | New name | File |
|----------|----------|------|
| `Mapper` | `InputMapper` | `src/Runtime/InputMapper.php` |
| `MapperProvider` | `InputMapperProvider` | `src/Runtime/InputMapperProvider.php` |
| `CallbackMapper` | `CallbackInputMapper` | `src/Runtime/CallbackInputMapper.php` |

### 3.2 Rename compiler factory classes

| Old name | New name |
|----------|----------|
| `DefaultMapperCompilerFactory` | `DefaultInputMapperCompilerFactory` |
| `MapperCompilerFactoryProvider` | `InputMapperCompilerFactoryProvider` |
| `DefaultMapperCompilerFactoryProvider` | `DefaultInputMapperCompilerFactoryProvider` |

### 3.3 Update `PhpCodeBuilder` method names

- `mapperMethod()` → `inputMapperMethod()` (or keep generic if shared)
- `mapperClass()` → `inputMapperClass()`
- `mapperFile()` → `inputMapperFile()`
- `mapperClassConstructor()` → `inputMapperClassConstructor()`

### 3.4 Update all internal references

- All `use` statements, type hints, PHPDoc across `src/` and `tests/`
- Generated mapper code references (the generated classes implement `InputMapper`)
- PHPStan extensions

### 3.5 Update test expectations

- Tests that reference `Mapper::class`, `MapperProvider`, etc.
- Generated code snapshots

### 3.6 Run full test suite — all tests must pass

### 3.7 Run static analysis (PHPStan) — must pass

---

## Phase 4: Core Output Runtime Infrastructure

New functionality — no BC breaks from here on.

### 4.1 Create `OutputMapper<T>` interface

`src/Runtime/OutputMapper.php`:
- `@template-contravariant T`
- Method: `map(mixed $data, array $path = []): mixed`

### 4.2 Create `CallbackOutputMapper`

`src/Runtime/CallbackOutputMapper.php`:
- Wraps a `Closure` as `OutputMapper<T>`
- Needed for generic inner mapper injection

### 4.3 Create `OutputMapperProvider`

`src/Runtime/OutputMapperProvider.php`:
- `get(string $className, array $innerOutputMappers = []): OutputMapper`
- Same compilation + caching infrastructure as `InputMapperProvider`
- Same file-locking approach for temp dir

### 4.4 Extend `PhpCodeBuilder` with output mapper methods

Add to `PhpCodeBuilder`:
- `outputMapperMethod(string $methodName, MapperCompiler $compiler): Method`
- `outputMapperClass(string $shortClassName, MapperCompiler $compiler): Class_`
- `outputMapperFile(string $className, MapperCompiler $compiler): array`
- `outputMapperClassConstructor()` — generates constructor with `OutputMapperProvider` + `$innerMappers` typed as `array{OutputMapper<T>}`

### 4.5 Create `PassthroughMapperCompiler`

`src/Compiler/Mapper/PassthroughMapperCompiler.php`:
- Constructor takes `TypeNode $type`
- `compile()` returns `new CompiledExpr($value)` — no transformation
- `getInputType()` and `getOutputType()` both return `$this->type`

### 4.6 Write unit tests for new runtime classes

- `OutputMapperTest.php` — basic interface contract
- `CallbackOutputMapperTest.php`
- `OutputMapperProviderTest.php`
- `PassthroughMapperCompilerTest.php`

---

## Phase 5: Scalar Output + First End-to-End Test

### 5.1 Add `OutputMapperCompilerProvider` to scalar attributes

For `MapInt`, `MapString`, `MapBool`, `MapFloat`, `MapMixed`:
```php
public function getOutputMapperCompiler(): MapperCompiler {
    return new PassthroughMapperCompiler(new IdentifierTypeNode('int')); // etc.
}
```

### 5.2 Create `ObjectOutputMapperCompiler`

`src/Compiler/Mapper/Output/ObjectOutputMapperCompiler.php`:
- Implements `GenericMapperCompiler`
- Constructor: `$className`, `$propertyMapperCompilers` (propertyName → [outputKey, MapperCompiler]), `$genericParameters`
- Reads public readonly promoted properties
- Builds output array: `['key' => compiledExpr, ...]`
- Respects `#[SourceKey]` for output key names
- Handles `Optional<T>` properties (conditional key inclusion)

### 5.3 Create `DefaultOutputMapperCompilerFactory`

`src/Compiler/MapperFactory/DefaultOutputMapperCompilerFactory.php`:
- Implements `MapperCompilerFactory`
- Object handling: reads public readonly constructor promoted properties
- Scalar types → `PassthroughMapperCompiler`
- Creates `ObjectOutputMapperCompiler` for objects

### 5.4 Create factory provider classes

- `OutputMapperCompilerFactoryProvider.php` (interface)
- `DefaultOutputMapperCompilerFactoryProvider.php` (implementation)

### 5.5 End-to-end test: flat scalar-only object

Test a simple DTO like:
```php
class PersonInput {
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
```
Verify: `$outputMapper->map(new PersonInput(1, 'John'))` returns `['id' => 1, 'name' => 'John']`.

### 5.6 Test `#[SourceKey]` on output

Verify that `#[SourceKey('full_name')]` produces key `full_name` in output array.

---

## Phase 6: Wrapper Output Compilers

### 6.1 Create `NullableOutputMapperCompiler`

`src/Compiler/Mapper/Output/NullableOutputMapperCompiler.php`:
- `$data === null ? null : mapInner($data)`

### 6.2 Create `OptionalOutputMapperCompiler`

`src/Compiler/Mapper/Output/OptionalOutputMapperCompiler.php`:
- `OptionalSome` → map inner value
- `OptionalNone` → signal "omit this key"

### 6.3 Add `OutputMapperCompilerProvider` to `MapNullable`

### 6.4 Update `ObjectOutputMapperCompiler` to handle optional properties

Generate `if ($data->prop->isDefined())` checks for `Optional<T>` properties.

### 6.5 Write tests for nullable and optional output

- Nullable field with value → serialized
- Nullable field with null → `null` in output
- Optional field with `OptionalSome` → included in output
- Optional field with `OptionalNone` → omitted from output

---

## Phase 7: Collection Output Compilers

### 7.1 Create `ListOutputMapperCompiler`

`src/Compiler/Mapper/Output/ListOutputMapperCompiler.php`:
- `foreach` + map each item, return `list<mixed>`

### 7.2 Create `ArrayOutputMapperCompiler`

`src/Compiler/Mapper/Output/ArrayOutputMapperCompiler.php`:
- `foreach` + map each key and value

### 7.3 Create `ArrayShapeOutputMapperCompiler`

`src/Compiler/Mapper/Output/ArrayShapeOutputMapperCompiler.php`:
- Build output array with known keys, map each value

### 7.4 Add `OutputMapperCompilerProvider` to `MapList`, `MapArray`, `MapArrayShape`

### 7.5 Update `DefaultOutputMapperCompilerFactory` for collection types

### 7.6 Write tests for collection output

- List of scalars
- List of objects
- Associative array
- Array shapes

---

## Phase 8: Special Type Output Compilers

### 8.1 Create `EnumOutputMapperCompiler`

`src/Compiler/Mapper/Output/EnumOutputMapperCompiler.php`:
- Generates `$data->value` for backed enums

### 8.2 Create `DateTimeImmutableOutputMapperCompiler`

`src/Compiler/Mapper/Output/DateTimeImmutableOutputMapperCompiler.php`:
- Generates `$data->format($format)`
- Handles timezone conversion if `$targetTimezone` is set

### 8.3 Add `OutputMapperCompilerProvider` to `MapEnum`, `MapDateTimeImmutable`

### 8.4 Update `DefaultOutputMapperCompilerFactory` for enum and datetime types

### 8.5 Write tests for enum and datetime output

- Backed enum → backing value
- DateTimeImmutable → formatted string
- DateTimeImmutable with timezone

---

## Phase 9: Delegate Output Compiler + Generics

### 9.1 Create `DelegateOutputMapperCompiler`

`src/Compiler/Mapper/Output/DelegateOutputMapperCompiler.php`:

**Mode 1: Template parameter reference** (e.g., `DelegateOutputMapperCompiler('T')`):
- Checks `$builder->getGenericParameters()`, finds `T` at offset `N`
- Generates `$this->innerMappers[N]->map($data, $path)`

**Mode 2: Concrete class reference** (e.g., `DelegateOutputMapperCompiler('CollectionInput', [PassthroughMapperCompiler('int')])`):
- Compiles inner mapper compilers into `CallbackOutputMapper` instances
- Generates `$this->provider->get(ClassName::class, [$innerMappers])->map($data, $path)`

### 9.2 Update `DefaultOutputMapperCompilerFactory` for generic types

- Detect `@template` parameters via `PhpDocTypeUtils::getGenericTypeDefinition()`
- Use `DelegateOutputMapperCompiler` for class references and template parameters
- Validate bounded generics

### 9.3 Update `ObjectOutputMapperCompiler` generic support

- Carries `genericParameters` from factory
- Implements `GenericMapperCompiler` interface

### 9.4 Write tests for generic output

- Simple generic: `CollectionInput<int>` — verify items are serialized correctly
- Bounded generic: `@template T of BackedEnum` with concrete enum type
- Nested generics: non-generic class referencing `InFilterInput<int>`, `EqualsFilterInput<ColorEnum>`
- Round-trip: `map_output(map_input($data)) === $data` for generic classes

---

## Phase 10: Discriminated Object Output

### 10.1 Create `DiscriminatedObjectOutputMapperCompiler`

`src/Compiler/Mapper/Output/DiscriminatedObjectOutputMapperCompiler.php`:
- Uses `instanceof` checks to dispatch to correct subtype mapper
- Generates `match(true) { $data instanceof Dog => ..., $data instanceof Cat => ..., default => throw }`
- Adds discriminator key to output array

### 10.2 Add `OutputMapperCompilerProvider` to `MapDiscriminatedObject`

### 10.3 Update `DefaultOutputMapperCompilerFactory` for discriminated objects

- Read `#[Discriminator]` attribute
- Create `DiscriminatedObjectOutputMapperCompiler` with subtype mappings

### 10.4 Write tests for discriminated object output

- Simple discriminated union
- With nested objects
- Round-trip test

---

## Phase 11: Comprehensive Testing

### 11.1 Round-trip tests

For every supported type combination, verify:
```php
$output = $outputMapper->map($inputMapper->map($data));
self::assertSame($data, $output);
```

Test cases:
- Flat scalar DTO
- Nested objects
- Lists and arrays of objects
- Nullable fields
- Optional fields
- Enum fields
- DateTime fields
- Discriminated objects
- Generic classes
- Combination of all above

### 11.2 Edge case tests

- Empty object (no properties)
- Object with all-optional properties (all `OptionalNone`)
- Deeply nested objects (3+ levels)
- Object with `#[SourceKey]` on every property
- `#[AllowExtraKeys]` has no effect on output (verify)

### 11.3 Error handling tests

- Wrong type passed to output mapper → `MappingFailedException`
- Null passed to non-nullable → `MappingFailedException`

---

## Phase 12: PHPStan Extensions

### 12.1 Extend existing PHPStan rules for output mapper generics

- `OutputMapperProvider::get()` return type based on class string argument
- `OutputMapper<T>::map()` parameter type

### 12.2 Run PHPStan on full codebase — must pass

---

## Phase 13: Unified MapperProvider (Optional)

### 13.1 Create combined `MapperProvider`

```php
class MapperProvider {
    public function getInputMapper(string $className): InputMapper;
    public function getOutputMapper(string $className): OutputMapper;
}
```

Delegates to `InputMapperProvider` and `OutputMapperProvider` internally.

### 13.2 Write tests for combined provider

---

## Dependency Graph

```
Phase 1 (move attributes)
    ↓
Phase 2 (attribute refactoring)
    ↓
Phase 3 (rename runtime)
    ↓
Phase 4 (output runtime infra) ─────────────────────────────────────┐
    ↓                                                                │
Phase 5 (scalar output + object compiler + factory + e2e test)       │
    ↓                                                                │
┌───┴───────────────┬──────────────────┐                             │
Phase 6 (wrappers)  Phase 7 (collections)  Phase 8 (enum/datetime)  │
└───┬───────────────┴──────────────────┘                             │
    ↓                                                                │
Phase 9 (delegate + generics) ←──────────────────────────────────────┘
    ↓
Phase 10 (discriminated objects)
    ↓
Phase 11 (comprehensive testing)
    ↓
Phase 12 (PHPStan extensions)
    ↓
Phase 13 (unified provider — optional)
```

Phases 6, 7, 8 can be done in parallel since they are independent of each other.
