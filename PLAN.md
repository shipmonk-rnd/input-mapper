# Output Mapper — Design Plan

## Goal

Extend this library to support **bidirectional mapping**: not only `mixed → T` (input/deserialization), but also `T → mixed` (output/serialization). The output direction takes a well-typed PHP object and produces an array structure suitable for `json_encode()`.

Since this is a major change, **BC breaks are acceptable** as long as a migration path exists.

---

## 1. Naming & Packaging Strategy

### 1.1. Library rename

The library is currently named `shipmonk/input-mapper`. With bidirectional support, the name should become direction-neutral:

- **Package**: `shipmonk/input-mapper` → `shipmonk/mapper` (or keep as-is with expanded scope)
- **Root namespace**: `ShipMonk\InputMapper` → `ShipMonk\Mapper`
- **Decision needed**: Whether to rename now or keep the existing name. The plan below assumes a rename to `ShipMonk\Mapper` for clarity, but the architecture works either way.

### 1.2. Terminology

| Concept | Current name | Proposed name |
|---------|-------------|---------------|
| `mixed → T` | "mapping" / "input mapping" | **"input mapping"** or **"deserialization"** |
| `T → mixed` | (new) | **"output mapping"** or **"serialization"** |
| Runtime interface (`mixed → T`) | `Mapper<T>` | `InputMapper<T>` |
| Runtime interface (`T → mixed`) | (new) | `OutputMapper<T>` |
| Compile-time interface (both directions) | `MapperCompiler` | `MapperCompiler` (unchanged — single unified interface) |

---

## 2. Architecture Overview

### 2.1. Key insight: `MapperCompiler` is direction-agnostic

The current `MapperCompiler` interface has three methods:

```php
interface MapperCompiler {
    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr;
    public function getInputType(): TypeNode;
    public function getOutputType(): TypeNode;
}
```

This interface is **already general enough** for both directions:

- **Input direction** (e.g. `MapInt`): `getInputType() → mixed`, `getOutputType() → int`. The `compile()` method validates and converts.
- **Output direction** (e.g. `NormalizeEnum`): `getInputType() → MyEnum`, `getOutputType() → string`. The `compile()` method reads the backing value.

The semantics of `getInputType()` and `getOutputType()` naturally flip — input compilers go from `mixed` to a specific type, output compilers go from a specific type to a JSON-compatible type. But the interface contract is identical. **There is no need for a separate `OutputMapperCompiler` interface.**

### 2.2. Attributes become configuration, not compilers

Currently, attributes like `MapDateTimeImmutable` **implement** `MapperCompiler` directly — they are both configuration holders and code generators. This conflation makes bidirectional support awkward because a single attribute would need to implement two different `compile()` methods.

**New approach**: Attributes are pure configuration objects that **provide** `MapperCompiler` instances for each direction via two separate marker interfaces:

```php
interface InputMapperCompilerProvider {
    public function getInputMapperCompiler(): MapperCompiler;
}

interface OutputMapperCompilerProvider {
    public function getOutputMapperCompiler(): MapperCompiler;
}
```

An attribute implements one or both interfaces depending on which directions it supports:

```php
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDateTimeImmutable implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{
    public function __construct(
        public readonly string|array $format = [DateTimeInterface::RFC3339, DateTimeInterface::RFC3339_EXTENDED],
        public readonly string $formatDescription = 'date-time string in RFC 3339 format',
        public readonly ?string $defaultTimezone = null,
        public readonly ?string $targetTimezone = null,
    ) {}

    public function getInputMapperCompiler(): MapperCompiler {
        return new DateTimeImmutableInputMapperCompiler(
            $this->format, $this->formatDescription, $this->defaultTimezone, $this->targetTimezone,
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler {
        // Naturally shares the same format config — solves the "format flow" problem
        return new DateTimeImmutableOutputMapperCompiler(
            is_array($this->format) ? $this->format[0] : $this->format,
            $this->targetTimezone,
        );
    }
}
```

This cleanly separates **configuration** (the attribute) from **code generation** (the `MapperCompiler` implementations).

### 2.3. Three-layer architecture (both directions)

```
Attributes (on user classes, implement InputMapperCompilerProvider / OutputMapperCompilerProvider)
    ↓ reflection
Compiler layer (MapperCompiler tree → PhpCodeBuilder → PHP AST → generated class)
    ↓ includes
Runtime layer (InputMapper<T> or OutputMapper<T> instances)
```

Both directions share the same `MapperCompiler` interface and `PhpCodeBuilder` infrastructure. Only the runtime interfaces (`InputMapper` vs `OutputMapper`) and the concrete `MapperCompiler` implementations differ.

---

## 3. Shared / Reusable Components (no changes needed)

These components are already direction-agnostic:

| Component | Why reusable |
|-----------|-------------|
| `MapperCompiler` interface | Unified for both directions (see §2.1) |
| `GenericMapperCompiler` interface | Generic type parameters work in both directions |
| `CompiledExpr` | Value object: expression + statements. Works for any code generation. |
| `PhpCodeBuilder` (base utilities) | AST builder helpers (`if`, `foreach`, `assign`, etc.) are general. |
| `PhpCodePrinter` | Just a pretty-printer. |
| `PhpDocTypeUtils` | Type resolution, subtype checking, generic parsing. |
| `GenericTypeParameter` / `GenericTypeDefinition` / `GenericTypeVariance` | Type system metadata. |
| `MappingFailedException` | Error reporting with JSON paths — useful for output errors too. |
| `Optional` / `OptionalSome` / `OptionalNone` | Represent optional fields — relevant on both sides. |

---

## 4. New Runtime Interfaces

### 4.1. `OutputMapper<T>`

```php
/**
 * @template-contravariant T
 */
interface OutputMapper
{
    /**
     * @param T $object
     * @param list<string|int> $path
     * @return mixed  // in practice: scalar|array|null (JSON-encodable)
     *
     * @throws MappingFailedException
     */
    public function normalize(
        mixed $object,
        array $path = [],
    ): mixed;
}
```

Note: `T` is **contravariant** here (an `OutputMapper<Animal>` can serialize any `Dog`).

### 4.2. `OutputMapperProvider`

Mirrors `MapperProvider`, but for the output direction:

```php
class OutputMapperProvider
{
    public function get(string $className, array $innerOutputMappers = []): OutputMapper;
    public function registerFactory(string $className, callable $factory): void;
}
```

Internally compiles and caches `OutputMapper` classes in `$tempDir`, same file-locking approach.

### 4.3. Rename existing runtime interfaces

- `Mapper<T>` → `InputMapper<T>`
- `MapperProvider` → `InputMapperProvider`
- `CallbackMapper` → `CallbackInputMapper`

### 4.4. Combined `MapperProvider` (optional convenience)

A single provider that can lazily create both input and output mappers:

```php
class MapperProvider
{
    public function getInputMapper(string $className): InputMapper;
    public function getOutputMapper(string $className): OutputMapper;
}
```

---

## 5. Attribute Refactoring (BC break)

### 5.1. Two provider interfaces

```php
interface InputMapperCompilerProvider {
    public function getInputMapperCompiler(): MapperCompiler;
}

interface OutputMapperCompilerProvider {
    public function getOutputMapperCompiler(): MapperCompiler;
}
```

### 5.2. Which attributes implement which interface

| Attribute | `InputMapperCompilerProvider` | `OutputMapperCompilerProvider` | Notes |
|-----------|:---:|:---:|-------|
| `MapInt` | yes | yes | Input: validates `is_int`. Output: pass-through. |
| `MapString` | yes | yes | Input: validates `is_string`. Output: pass-through. |
| `MapBool` | yes | yes | Input: validates `is_bool`. Output: pass-through. |
| `MapFloat` | yes | yes | Input: validates `is_float`. Output: pass-through. |
| `MapMixed` | yes | yes | Both: pass-through. |
| `MapList` | yes | yes | Input: validates list + maps items. Output: iterates + normalizes items. |
| `MapArray` | yes | yes | Input: validates array + maps k/v. Output: iterates + normalizes k/v. |
| `MapArrayShape` | yes | yes | Input: validates shape. Output: builds shape. |
| `MapObject` | yes | yes | Input: validates array → constructs object. Output: reads properties → builds array. |
| `MapEnum` | yes | yes | Input: `tryFrom()`. Output: `->value`. |
| `MapDateTimeImmutable` | yes | yes | Input: `createFromFormat()`. Output: `->format()`. |
| `MapDiscriminatedObject` | yes | yes | Input: dispatch by key. Output: dispatch by `instanceof`. |
| `MapNullable` | yes | yes | Both: null check + delegate to inner. |
| `MapOptional` | yes | no | Input-only concept (undefined key handling). |
| `MapDefaultValue` | yes | no | Input-only concept (default values for missing keys). |
| `ValidatedMapperCompiler` | yes | no | Input-only concept (validation). |
| `#[Optional]` | (not a compiler provider) | — | Stays as-is: consumed by the factory, not a compiler provider. |
| All `ValidatorCompiler` attrs | (not a compiler provider) | — | Stays as-is: consumed by the factory. |

### 5.3. Factory attribute discovery (BC break)

Currently the factory discovers mapper attributes via:
```php
$parameterReflection->getAttributes(MapperCompiler::class, ReflectionAttribute::IS_INSTANCEOF)
```

This changes to:
```php
// For input direction:
$parameterReflection->getAttributes(InputMapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF)

// For output direction:
$parameterReflection->getAttributes(OutputMapperCompilerProvider::class, ReflectionAttribute::IS_INSTANCEOF)
```

Then calling `->getInputMapperCompiler()` or `->getOutputMapperCompiler()` on the attribute instance.

### 5.4. Example: `MapDateTimeImmutable` refactored

```php
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDateTimeImmutable implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{
    public function __construct(
        public readonly string|array $format = [...],
        public readonly string $formatDescription = '...',
        public readonly ?string $defaultTimezone = null,
        public readonly ?string $targetTimezone = null,
    ) {}

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DateTimeImmutableInputMapperCompiler(
            $this->format, $this->formatDescription,
            $this->defaultTimezone, $this->targetTimezone,
        );
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new DateTimeImmutableOutputMapperCompiler(
            is_array($this->format) ? $this->format[0] : $this->format,
            $this->targetTimezone,
        );
    }
}
```

### 5.5. Example: `MapInt` refactored

For scalars, the input and output compilers are simple enough that they can be separate small classes:

```php
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapInt implements InputMapperCompilerProvider, OutputMapperCompilerProvider
{
    public function getInputMapperCompiler(): MapperCompiler
    {
        return new IntInputMapperCompiler();  // validates is_int, returns $value
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        return new ScalarPassthroughMapperCompiler('int');  // just returns $value
    }
}
```

Note: all scalar output compilers can share a single `ScalarPassthroughMapperCompiler` that simply returns `$value` unchanged (since scalars are already JSON-encodable).

---

## 6. MapperCompiler Implementations (output direction)

Each existing input `MapperCompiler` needs an output counterpart. The output versions are generally **much simpler** because they don't need validation — the input is already typed.

### 6.1. Scalars

A single `ScalarPassthroughMapperCompiler` covers all scalar types:

```php
class ScalarPassthroughMapperCompiler implements MapperCompiler
{
    public function __construct(public readonly string $typeName) {} // 'int', 'string', etc.

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        return new CompiledExpr($value); // no statements, no validation — just pass through
    }

    public function getInputType(): TypeNode { return new IdentifierTypeNode($this->typeName); }
    public function getOutputType(): TypeNode { return new IdentifierTypeNode($this->typeName); }
}
```

### 6.2. Objects

| Output `MapperCompiler` | Behavior |
|------------------------|----------|
| `NormalizeObject` | Read each property, recursively normalize, build `['key' => value, ...]` array |
| `DelegateOutputMapperCompiler` | Delegate to `$this->provider->get(ClassName::class)->normalize($value)` |
| `NormalizeEnum` | `$value->value` (BackedEnum backing value) |
| `NormalizeDateTimeImmutable` | `$value->format($format)` |
| `NormalizeDiscriminatedObject` | `instanceof` checks to dispatch to the correct subtype normalizer |

#### `NormalizeObject` — the most important new class

This is the inverse of `MapObject`. It implements the same `MapperCompiler` interface. Key differences from input:

- **No validation** of input shape (input is a typed object)
- **Reads properties** instead of array keys: `$value->propertyName`
- **Builds output array**: `['key1' => normalize($value->prop1), 'key2' => normalize($value->prop2)]`
- **`#[SourceKey]` is respected**: if `#[SourceKey('json_key')]` is on a property, the output array key is `json_key`
- **Optional properties**: if the property type is `Optional<T>`, the key is omitted from output when `OptionalNone`
- **No extra-key checking** (irrelevant for output)

```php
class NormalizeObject implements GenericMapperCompiler  // same interface as MapObject!
{
    public function __construct(
        public readonly string $className,
        public readonly array $propertyMapperCompilers, // paramName => [outputKey, MapperCompiler]
        public readonly array $genericParameters = [],
    ) {}

    public function getInputType(): TypeNode { /* IdentifierTypeNode($this->className) */ }
    public function getOutputType(): TypeNode { /* mixed or array shape */ }
}
```

#### `NormalizeDiscriminatedObject`

Uses `instanceof` checks to determine which subtype normalizer to use:

```php
// Generated code sketch:
match (true) {
    $value instanceof DogInput => $this->normalizeDog($value, $path),
    $value instanceof CatInput => $this->normalizeCat($value, $path),
    default => throw MappingFailedException::incorrectType(...)
};
```

### 6.3. Collections

| Output `MapperCompiler` | Behavior |
|------------------------|----------|
| `NormalizeList` | `foreach` + normalize each item, return `list<mixed>` |
| `NormalizeArray` | `foreach` + normalize each key and value |
| `NormalizeArrayShape` | Build output array with known keys, normalize each value |

### 6.4. Wrappers

| Output `MapperCompiler` | Behavior |
|------------------------|----------|
| `NormalizeNullable` | `$value === null ? null : normalizeInner($value)` |
| `NormalizeOptional` | If `OptionalSome`, normalize value; if `OptionalNone`, signal "omit this key" |
| `ChainMapperCompiler` | Already direction-agnostic — can chain output compilers too |

Note: `ChainMapperCompiler` is already reusable as-is since it just pipes `compile()` output to next input.

### 6.5. Handling `Optional` on output

When a property has type `Optional<T>`:
- On **input**: missing key → `OptionalNone`, present key → `OptionalSome(mapped_value)`
- On **output**: `OptionalNone` → **omit key entirely** from output array, `OptionalSome(value)` → normalize the inner value

This means `NormalizeObject` needs to handle optional properties specially — it must generate an `if ($value->prop->isDefined())` check and conditionally include the key.

---

## 7. Compiler Factory

### 7.1. `MapperCompilerFactory` (unified interface)

The factory interface can remain the same — it creates `MapperCompiler` instances from types:

```php
interface MapperCompilerFactory
{
    public function create(TypeNode $type, array $options = []): MapperCompiler;
}
```

Two implementations: `DefaultInputMapperCompilerFactory` and `DefaultOutputMapperCompilerFactory`.

### 7.2. `DefaultOutputMapperCompilerFactory`

Mirrors `DefaultInputMapperCompilerFactory` but builds output `MapperCompiler` trees:

- **Object handling**: Reads **constructor promoted properties** (same source of truth as input). For each property:
  - Determines the type from reflection + PHPDoc
  - Reads `#[SourceKey]` attribute → output key name
  - Reads `OutputMapperCompilerProvider` attributes → custom output compiler override
  - Ignores `#[Optional]` (input-only) and `ValidatorCompiler` attributes (input-only)
  - Creates the inner output `MapperCompiler` for the property type
- **Scalar types**: Returns `ScalarPassthroughMapperCompiler`
- **Enums**: Returns `NormalizeEnum`
- **DateTime**: Returns `NormalizeDateTimeImmutable`
- **Generics**: Same approach as input — `DelegateOutputMapperCompiler` with inner mapper compilers
- **Discriminated objects**: Reads `#[Discriminator]` attribute, creates `NormalizeDiscriminatedObject`

### 7.3. Property discovery strategy

1. **Primary approach**: Read **constructor promoted properties** (same source of truth as input mapper). This ensures symmetry: every constructor parameter that the input mapper reads from the array, the output mapper writes back to the array.
2. **Fallback**: If needed, also support reading all public properties (for classes not using constructor promotion).
3. **Key mapping**: `#[SourceKey('json_key')]` on a constructor parameter means: input reads from `json_key`, output writes to `json_key`.

This ensures **round-trip fidelity**: `normalize(map($data)) ≈ $data` (modulo optional fields and default values).

---

## 8. PhpCodeBuilder Extensions

The builder needs output-mapper-specific methods, mirroring the input-mapper ones:

```php
class PhpCodeBuilder
{
    // Existing (rename for clarity):
    public function inputMapperMethod(string $methodName, MapperCompiler $compiler): Method;
    public function inputMapperClass(string $shortClassName, MapperCompiler $compiler): Class_;
    public function inputMapperFile(string $className, MapperCompiler $compiler): array;

    // New (output-specific):
    public function outputMapperMethod(string $methodName, MapperCompiler $compiler): Method;
    public function outputMapperClass(string $shortClassName, MapperCompiler $compiler): Class_;
    public function outputMapperFile(string $className, MapperCompiler $compiler): array;
}
```

Both `inputMapperMethod()` and `outputMapperMethod()` accept the same `MapperCompiler` interface. The difference is in the generated class structure:
- `inputMapperClass()` generates a class implementing `InputMapper<T>` with a `map()` public method
- `outputMapperClass()` generates a class implementing `OutputMapper<T>` with a `normalize()` public method

The `mapperMethod()` helper (generating private methods for sub-compilers) can likely stay shared since it just calls `$compiler->compile()` and wraps the result in a method.

**Alternative**: Use separate builder classes (`InputMapperCodeBuilder`, `OutputMapperCodeBuilder`) extending a shared `PhpCodeBuilder` base. This avoids bloating a single class.

---

## 9. Attribute Reuse & New Attributes

### 9.1. Reused attributes (work for both directions)

| Attribute | Input meaning | Output meaning |
|-----------|--------------|----------------|
| `#[SourceKey('k')]` | Read from input key `k` | Write to output key `k` |
| `#[Discriminator]` | Dispatch input by discriminator key | Dispatch output by `instanceof` |
| `#[AllowExtraKeys]` | Don't error on unknown keys | (no effect on output) |

### 9.2. Attributes that only implement `InputMapperCompilerProvider`

| Attribute | Why input-only |
|-----------|---------------|
| `MapOptional` | Handles undefined keys — an input-only concept |
| `MapDefaultValue` | Default values for missing keys — an input-only concept |
| `ValidatedMapperCompiler` | Wraps with validation — an input-only concept |

### 9.3. Attributes that are not compiler providers (unchanged)

| Attribute | Role |
|-----------|------|
| `#[Optional(default: ...)]` | Consumed by the factory directly, not a compiler provider |
| All `ValidatorCompiler` attrs | Consumed by the factory directly |

### 9.4. New output-specific attributes (can be added incrementally)

| Attribute | Purpose |
|-----------|---------|
| `#[OutputKey('k')]` | Override the output key name independently from `#[SourceKey]` |
| `#[Omit]` or `#[IgnoreOnOutput]` | Skip this property during output normalization |

---

## 10. Migration Plan (BC Breaks)

### 10.1. Phase 1: Attribute refactoring (BC break)

- Attributes no longer implement `MapperCompiler` directly
- Instead they implement `InputMapperCompilerProvider` and/or `OutputMapperCompilerProvider`
- Extract the current `compile()` logic from each attribute into a dedicated `*InputMapperCompiler` class
- **Migration**: Users with custom `MapperCompiler` attributes must split them into attribute + compiler class. Built-in attributes are migrated automatically.

### 10.2. Phase 2: Rename namespace (BC break)

- `ShipMonk\InputMapper\*` → `ShipMonk\Mapper\*`
- **Migration**: Find-and-replace in `use` statements. Provide a migration guide or rector rule.

### 10.3. Phase 3: Rename core runtime interfaces (BC break)

- `Mapper<T>` → `InputMapper<T>`
- `MapperProvider` → `InputMapperProvider`
- `CallbackMapper` → `CallbackInputMapper`
- `DefaultMapperCompilerFactory` → `DefaultInputMapperCompilerFactory`
- `MapperCompilerFactoryProvider` → `InputMapperCompilerFactoryProvider`
- **Migration**: Automated rename. The old names could temporarily exist as deprecated aliases.

### 10.4. Phase 4: Add output mapper (no BC break from here)

- Add all output `MapperCompiler` implementations (`NormalizeObject`, `NormalizeEnum`, etc.)
- Add `OutputMapper<T>` interface and `OutputMapperProvider`
- Add `DefaultOutputMapperCompilerFactory`
- Add `OutputMapperCompilerProvider` implementations to existing attributes

### 10.5. Phase 5: Unified provider (optional)

- Add a combined `MapperProvider` that delegates to both directions

---

## 11. File Structure (proposed)

```
src/
  Compiler/
    Mapper/
      MapperCompiler.php                  # UNCHANGED — unified interface for both directions
      GenericMapperCompiler.php           # UNCHANGED
      UndefinedAwareMapperCompiler.php    # UNCHANGED (input-only concept, but valid MapperCompiler)
      MapRuntime.php                      # UNCHANGED

      InputMapperCompilerProvider.php     # NEW — marker interface for attributes
      OutputMapperCompilerProvider.php    # NEW — marker interface for attributes

      Array/
        # Attributes (configuration, implement provider interfaces):
        MapList.php                       # REFACTORED — now implements Input+OutputMapperCompilerProvider
        MapArray.php
        MapArrayShape.php
        # Input compiler implementations:
        ListInputMapperCompiler.php       # NEW — extracted from old MapList::compile()
        ArrayInputMapperCompiler.php
        ArrayShapeInputMapperCompiler.php
        # Output compiler implementations:
        NormalizeList.php                 # NEW
        NormalizeArray.php
        NormalizeArrayShape.php

      Mixed/
        MapMixed.php                      # REFACTORED
        MixedPassthroughMapperCompiler.php

      Object/
        # Attributes:
        MapObject.php                     # REFACTORED
        MapEnum.php                       # REFACTORED
        MapDateTimeImmutable.php          # REFACTORED
        MapDiscriminatedObject.php        # REFACTORED
        SourceKey.php                     # UNCHANGED
        AllowExtraKeys.php                # UNCHANGED
        Discriminator.php                 # UNCHANGED
        # Input compilers:
        ObjectInputMapperCompiler.php     # NEW — extracted from old MapObject::compile()
        EnumInputMapperCompiler.php
        DateTimeImmutableInputMapperCompiler.php
        DiscriminatedObjectInputMapperCompiler.php
        DelegateInputMapperCompiler.php   # REFACTORED from DelegateMapperCompiler
        # Output compilers:
        NormalizeObject.php               # NEW
        NormalizeEnum.php
        NormalizeDateTimeImmutable.php
        NormalizeDiscriminatedObject.php
        DelegateOutputMapperCompiler.php  # NEW

      Scalar/
        # Attributes:
        MapInt.php                        # REFACTORED
        MapString.php
        MapBool.php
        MapFloat.php
        # Input compilers:
        IntInputMapperCompiler.php        # NEW — extracted from old MapInt::compile()
        StringInputMapperCompiler.php
        BoolInputMapperCompiler.php
        FloatInputMapperCompiler.php
        # Output compiler (shared):
        ScalarPassthroughMapperCompiler.php  # NEW — single class for all scalar output

      Wrapper/
        # Attributes:
        MapNullable.php                   # REFACTORED — implements both providers
        MapOptional.php                   # REFACTORED — implements only InputMapperCompilerProvider
        MapDefaultValue.php               # REFACTORED — implements only InputMapperCompilerProvider
        ValidatedMapperCompiler.php       # REFACTORED — implements only InputMapperCompilerProvider
        # Input compilers:
        NullableInputMapperCompiler.php   # NEW — extracted
        OptionalInputMapperCompiler.php
        DefaultValueInputMapperCompiler.php
        ValidatedInputMapperCompiler.php
        # Output compilers:
        NormalizeNullable.php             # NEW
        NormalizeOptional.php             # NEW
        # Shared:
        ChainMapperCompiler.php           # UNCHANGED — already direction-agnostic

    MapperFactory/
      MapperCompilerFactory.php           # UNCHANGED — unified interface
      DefaultInputMapperCompilerFactory.php   # RENAMED from DefaultMapperCompilerFactory
      DefaultOutputMapperCompilerFactory.php  # NEW
      InputMapperCompilerFactoryProvider.php  # RENAMED
      DefaultInputMapperCompilerFactoryProvider.php  # RENAMED
      OutputMapperCompilerFactoryProvider.php  # NEW
      DefaultOutputMapperCompilerFactoryProvider.php  # NEW

    Php/                                  # Shared
      PhpCodeBuilder.php                  # Extended with outputMapper* methods
      PhpCodePrinter.php                  # UNCHANGED

    Type/                                 # Shared (unchanged)
      GenericTypeParameter.php
      PhpDocTypeUtils.php
      ...

    Validator/                            # Input-only (unchanged)
      ValidatorCompiler.php
      ...

    CompiledExpr.php                      # Shared (unchanged)

  Runtime/
    InputMapper.php                       # RENAMED from Mapper.php
    InputMapperProvider.php               # RENAMED from MapperProvider.php
    OutputMapper.php                      # NEW
    OutputMapperProvider.php              # NEW
    CallbackInputMapper.php              # RENAMED from CallbackMapper.php
    CallbackOutputMapper.php             # NEW
    Optional.php                          # UNCHANGED
    OptionalSome.php                      # UNCHANGED
    OptionalNone.php                      # UNCHANGED
    Exception/
      MappingFailedException.php          # UNCHANGED
```

---

## 12. Implementation Order

### Step 1: Attribute refactoring (BC break, no new functionality)
- Create `InputMapperCompilerProvider` and `OutputMapperCompilerProvider` interfaces
- For each existing attribute that implements `MapperCompiler`:
  - Extract `compile()`, `getInputType()`, `getOutputType()` into a new `*InputMapperCompiler` class
  - Make the attribute implement `InputMapperCompilerProvider` returning the extracted compiler
  - Remove `MapperCompiler` implementation from the attribute
- Update `DefaultMapperCompilerFactory` to discover attributes via `InputMapperCompilerProvider` instead of `MapperCompiler`
- All existing tests should still pass (same behavior, different wiring)

### Step 2: Rename (BC break, no new functionality)
- Namespace rename `ShipMonk\InputMapper` → `ShipMonk\Mapper`
- Runtime renames: `Mapper` → `InputMapper`, `MapperProvider` → `InputMapperProvider`, etc.
- Factory renames: `DefaultMapperCompilerFactory` → `DefaultInputMapperCompilerFactory`, etc.
- Provide migration guide / rector rule

### Step 3: Core output runtime
- `OutputMapper<T>` interface
- `OutputMapperProvider` class (compilation + caching infrastructure)
- `PhpCodeBuilder` extensions (`outputMapperMethod`, `outputMapperClass`, `outputMapperFile`)
- `CallbackOutputMapper`

### Step 4: Scalar output compilers
- `ScalarPassthroughMapperCompiler` (covers int, string, bool, float, mixed)
- Add `OutputMapperCompilerProvider` to `MapInt`, `MapString`, `MapBool`, `MapFloat`, `MapMixed`
- End-to-end test: compile + run an output mapper for a flat scalar-only object

### Step 5: Object output compiler
- `NormalizeObject` — the most important and complex piece
- `DelegateOutputMapperCompiler` — for nested object references
- Add `OutputMapperCompilerProvider` to `MapObject`

### Step 6: Wrapper output compilers
- `NormalizeNullable`
- `NormalizeOptional`
- Add `OutputMapperCompilerProvider` to `MapNullable`

### Step 7: Collection output compilers
- `NormalizeList`
- `NormalizeArray`
- `NormalizeArrayShape`
- Add `OutputMapperCompilerProvider` to `MapList`, `MapArray`, `MapArrayShape`

### Step 8: Special type output compilers
- `NormalizeEnum` + add `OutputMapperCompilerProvider` to `MapEnum`
- `NormalizeDateTimeImmutable` + add `OutputMapperCompilerProvider` to `MapDateTimeImmutable`

### Step 9: Discriminated object output
- `NormalizeDiscriminatedObject` + add `OutputMapperCompilerProvider` to `MapDiscriminatedObject`

### Step 10: Output compiler factory
- `DefaultOutputMapperCompilerFactory` — auto-creates output `MapperCompiler` trees from class reflection
- `OutputMapperCompilerFactoryProvider` + `DefaultOutputMapperCompilerFactoryProvider`

### Step 11: Tests
- Unit tests mirroring existing input mapper tests
- Round-trip tests: `normalize(map($data)) === $data` for various type combinations
- Tests for edge cases: optional fields, discriminated objects, generics, date formats

### Step 12: PHPStan extensions
- Extend existing PHPStan rules for output mapper generics

---

## 13. Example: End-to-End Usage

```php
// User class (unchanged from current usage)
class PersonInput
{
    public function __construct(
        public readonly int $id,
        #[SourceKey('full_name')]
        public readonly string $name,
        #[Optional(default: null)]
        public readonly ?string $email,
    ) {}
}

// Input mapping (current, renamed interface)
$inputMapper = $inputMapperProvider->get(PersonInput::class);
$person = $inputMapper->map(['id' => 1, 'full_name' => 'John', 'email' => 'john@example.com']);

// Output mapping (new)
$outputMapper = $outputMapperProvider->get(PersonInput::class);
$array = $outputMapper->normalize($person);
// Result: ['id' => 1, 'full_name' => 'John', 'email' => 'john@example.com']
$json = json_encode($array);
```

---

## 14. Open Questions

1. **Naming**: `normalize()` vs `serialize()` vs `toArray()` vs `map()` for the output method name?
2. **Should the namespace rename happen?** Keeping `ShipMonk\InputMapper` avoids a BC break; adding `OutputMapper` alongside is simpler but asymmetric.
3. **Property access strategy**: Should the output mapper only support public readonly promoted properties, or also support getters / non-promoted properties?
4. **Naming convention for extracted compilers**: The plan uses `*InputMapperCompiler` (e.g. `IntInputMapperCompiler`). Alternatives: keep the `Map*` prefix for input (e.g. `MapIntCompiler`) and `Normalize*` for output.
5. **Output-specific validation**: Should there be any output-side validation? (e.g., "assert this value is non-null before serializing"). Probably not needed initially.
