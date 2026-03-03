# Output Mapper — Design Plan

## Goal

Extend this library to support **bidirectional mapping**: not only `mixed → T` (input/deserialization), but also `T → mixed` (output/serialization). The output direction takes a well-typed PHP object and produces an array structure suitable for `json_encode()`.

Since this is a major change, **BC breaks are acceptable** as long as a migration path exists.

---

## 1. Naming & Packaging Strategy

### 1.1. Keep existing package name and namespace

The library keeps its current name and namespace for now:

- **Package**: `shipmonk/input-mapper` (unchanged)
- **Root namespace**: `ShipMonk\InputMapper` (unchanged)

This avoids a disruptive namespace rename. The name "input-mapper" becomes a historical artifact, but the namespace is stable.

### 1.2. Terminology

| Concept | Current name | Proposed name |
|---------|-------------|---------------|
| `mixed → T` | "mapping" / "input mapping" | **"input mapping"** |
| `T → mixed` | (new) | **"output mapping"** |
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

- **Input direction** (e.g. `IntInputMapperCompiler`): `getInputType() → mixed`, `getOutputType() → int`. The `compile()` method validates and converts.
- **Output direction** (e.g. `EnumOutputMapperCompiler`): `getInputType() → MyEnum`, `getOutputType() → string`. The `compile()` method reads the backing value.

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

### 2.3. Attributes move to `src/Compiler/Attribute/` (BC break)

All attributes currently scattered under `src/Compiler/Mapper/{Array,Object,Scalar,Wrapper}/` move to a dedicated `src/Compiler/Attribute/` directory. This separates the configuration layer (attributes) from the code generation layer (compiler implementations).

### 2.4. Three-layer architecture (both directions)

```
Attributes (src/Compiler/Attribute/, implement InputMapperCompilerProvider / OutputMapperCompilerProvider)
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
     * @param T $data
     * @param list<string|int> $path
     * @return mixed  // in practice: scalar|array|null (JSON-encodable)
     *
     * @throws MappingFailedException
     */
    public function map(
        mixed $data,
        array $path = [],
    ): mixed;
}
```

Note: `T` is **contravariant** here (an `OutputMapper<Animal>` can serialize any `Dog`). The method is named `map()` to match the generic nature of the interface — while object-to-JSON is the primary target, the interface supports any `T → mixed` transformation.

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
| `MapList` | yes | yes | Input: validates list + maps items. Output: iterates + maps items. |
| `MapArray` | yes | yes | Input: validates array + maps k/v. Output: iterates + maps k/v. |
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
        return new PassthroughMapperCompiler(new IdentifierTypeNode('int'));  // just returns $value
    }
}
```

Note: all scalar output compilers can share a single `PassthroughMapperCompiler` that simply returns `$value` unchanged (since scalars are already JSON-encodable).

---

## 6. MapperCompiler Implementations (output direction)

Each existing input `MapperCompiler` needs an output counterpart. The output versions are generally **much simpler** because they don't need validation — the input is already typed.

### 6.1. Naming convention

All compiler implementations follow the pattern `{Kind}InputMapperCompiler` and `{Kind}OutputMapperCompiler`:

| Input compiler | Output compiler |
|---------------|----------------|
| `IntInputMapperCompiler` | `PassthroughMapperCompiler` (shared) |
| `StringInputMapperCompiler` | `PassthroughMapperCompiler` (shared) |
| `BoolInputMapperCompiler` | `PassthroughMapperCompiler` (shared) |
| `FloatInputMapperCompiler` | `PassthroughMapperCompiler` (shared) |
| `ObjectInputMapperCompiler` | `ObjectOutputMapperCompiler` |
| `EnumInputMapperCompiler` | `EnumOutputMapperCompiler` |
| `DateTimeImmutableInputMapperCompiler` | `DateTimeImmutableOutputMapperCompiler` |
| `DiscriminatedObjectInputMapperCompiler` | `DiscriminatedObjectOutputMapperCompiler` |
| `ListInputMapperCompiler` | `ListOutputMapperCompiler` |
| `ArrayInputMapperCompiler` | `ArrayOutputMapperCompiler` |
| `ArrayShapeInputMapperCompiler` | `ArrayShapeOutputMapperCompiler` |
| `NullableInputMapperCompiler` | `NullableOutputMapperCompiler` |
| `OptionalInputMapperCompiler` | `OptionalOutputMapperCompiler` |
| `DelegateInputMapperCompiler` | `DelegateOutputMapperCompiler` |

### 6.2. `PassthroughMapperCompiler`

A single reusable compiler for any type that needs no transformation:

```php
class PassthroughMapperCompiler implements MapperCompiler
{
    public function __construct(public readonly TypeNode $type) {}

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        return new CompiledExpr($value); // no statements, no validation — just pass through
    }

    public function getInputType(): TypeNode { return $this->type; }
    public function getOutputType(): TypeNode { return $this->type; }
}
```

Accepts any `TypeNode`, so it works for scalars (`int`, `string`, `bool`, `float`), `mixed`, or any other type that is already in its target form.

### 6.3. Objects

#### `ObjectOutputMapperCompiler` — the most important new class

This is the inverse of `ObjectInputMapperCompiler`. It implements the same `MapperCompiler` interface. Key differences from input:

- **No validation** of input shape (input is a typed object)
- **Reads public readonly promoted properties**: `$data->propertyName`
- **Builds output array**: `['key1' => map($data->prop1), 'key2' => map($data->prop2)]`
- **`#[SourceKey]` is respected**: if `#[SourceKey('json_key')]` is on a property, the output array key is `json_key`
- **Optional properties**: if the property type is `Optional<T>`, the key is omitted from output when `OptionalNone`
- **No extra-key checking** (irrelevant for output)

```php
class ObjectOutputMapperCompiler implements GenericMapperCompiler
{
    public function __construct(
        public readonly string $className,
        public readonly array $propertyMapperCompilers, // propertyName => [outputKey, MapperCompiler]
        public readonly array $genericParameters = [],
    ) {}

    public function getInputType(): TypeNode { /* IdentifierTypeNode($this->className) */ }
    public function getOutputType(): TypeNode { /* mixed or array shape */ }
}
```

Only **public readonly promoted properties** are supported for now. This covers the vast majority of use cases (DTOs, value objects) and ensures symmetry with the input mapper which reads constructor parameters.

#### `DiscriminatedObjectOutputMapperCompiler`

Uses `instanceof` checks to determine which subtype mapper to use:

```php
// Generated code sketch:
match (true) {
    $data instanceof DogInput => $this->mapDog($data, $path),
    $data instanceof CatInput => $this->mapCat($data, $path),
    default => throw MappingFailedException::incorrectType(...)
};
```

### 6.4. Collections

| Output `MapperCompiler` | Behavior |
|------------------------|----------|
| `ListOutputMapperCompiler` | `foreach` + map each item, return `list<mixed>` |
| `ArrayOutputMapperCompiler` | `foreach` + map each key and value |
| `ArrayShapeOutputMapperCompiler` | Build output array with known keys, map each value |

### 6.5. Wrappers

| Output `MapperCompiler` | Behavior |
|------------------------|----------|
| `NullableOutputMapperCompiler` | `$data === null ? null : mapInner($data)` |
| `OptionalOutputMapperCompiler` | If `OptionalSome`, map value; if `OptionalNone`, signal "omit this key" |
| `ChainMapperCompiler` | Already direction-agnostic — can chain output compilers too |

Note: `ChainMapperCompiler` is already reusable as-is since it just pipes `compile()` output to next input.

### 6.6. Handling `Optional` on output

When a property has type `Optional<T>`:
- On **input**: missing key → `OptionalNone`, present key → `OptionalSome(mapped_value)`
- On **output**: `OptionalNone` → **omit key entirely** from output array, `OptionalSome(value)` → map the inner value

This means `ObjectOutputMapperCompiler` needs to handle optional properties specially — it must generate an `if ($data->prop->isDefined())` check and conditionally include the key.

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

- **Object handling**: Reads **public readonly constructor promoted properties** (same source of truth as input). For each property:
  - Determines the type from reflection + PHPDoc
  - Reads `#[SourceKey]` attribute → output key name
  - Reads `OutputMapperCompilerProvider` attributes → custom output compiler override
  - Ignores `#[Optional]` (input-only) and `ValidatorCompiler` attributes (input-only)
  - Creates the inner output `MapperCompiler` for the property type
- **Scalar types**: Returns `PassthroughMapperCompiler`
- **Enums**: Returns `EnumOutputMapperCompiler`
- **DateTime**: Returns `DateTimeImmutableOutputMapperCompiler`
- **Generics**: Same approach as input — `DelegateOutputMapperCompiler` with inner mapper compilers
- **Discriminated objects**: Reads `#[Discriminator]` attribute, creates `DiscriminatedObjectOutputMapperCompiler`

### 7.3. Property discovery strategy

Only **public readonly promoted constructor properties** are supported. This:

1. **Ensures symmetry**: Every constructor parameter that the input mapper reads from the array, the output mapper writes back to the array.
2. **Covers the primary use case**: DTOs and value objects with promoted properties are the standard pattern.
3. **Keeps it simple**: No need to handle getters, non-promoted properties, or complex access patterns.
4. **Key mapping**: `#[SourceKey('json_key')]` on a constructor parameter means: input reads from `json_key`, output writes to `json_key`.

This ensures **round-trip fidelity**: `map_output(map_input($data)) ≈ $data` (modulo optional fields and default values).

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
- `outputMapperClass()` generates a class implementing `OutputMapper<T>` with a `map()` public method

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
| `#[Omit]` or `#[IgnoreOnOutput]` | Skip this property during output mapping |

---

## 10. Migration Plan (BC Breaks)

### 10.1. Phase 1: Move attributes to `src/Compiler/Attribute/` (BC break)

- All attribute classes move from their current locations under `src/Compiler/Mapper/` to `src/Compiler/Attribute/`
- Namespace changes: e.g. `ShipMonk\InputMapper\Compiler\Mapper\Scalar\MapInt` → `ShipMonk\InputMapper\Compiler\Attribute\MapInt`
- **Migration**: Find-and-replace `use` statements.

### 10.2. Phase 2: Attribute refactoring (BC break)

- Attributes no longer implement `MapperCompiler` directly
- Instead they implement `InputMapperCompilerProvider` and/or `OutputMapperCompilerProvider`
- Extract the current `compile()` logic from each attribute into a dedicated `{Kind}InputMapperCompiler` class
- **Migration**: Users with custom `MapperCompiler` attributes must split them into attribute + compiler class. Built-in attributes are migrated automatically.

### 10.3. Phase 3: Rename core runtime interfaces (BC break)

- `Mapper<T>` → `InputMapper<T>`
- `MapperProvider` → `InputMapperProvider`
- `CallbackMapper` → `CallbackInputMapper`
- `DefaultMapperCompilerFactory` → `DefaultInputMapperCompilerFactory`
- `MapperCompilerFactoryProvider` → `InputMapperCompilerFactoryProvider`
- **Migration**: Automated rename. The old names could temporarily exist as deprecated aliases.

### 10.4. Phase 4: Add output mapper (no BC break from here)

- Add all output `MapperCompiler` implementations (`ObjectOutputMapperCompiler`, `EnumOutputMapperCompiler`, etc.)
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
    Attribute/                            # NEW location — all attributes moved here (BC break)
      # Mapper compiler provider interfaces:
      InputMapperCompilerProvider.php     # NEW
      OutputMapperCompilerProvider.php    # NEW
      # Mapping attributes (configuration only, implement provider interfaces):
      MapInt.php                          # MOVED + REFACTORED
      MapString.php
      MapBool.php
      MapFloat.php
      MapMixed.php
      MapList.php
      MapArray.php
      MapArrayShape.php
      MapObject.php
      MapEnum.php
      MapDateTimeImmutable.php
      MapDiscriminatedObject.php
      MapNullable.php
      MapOptional.php                     # InputMapperCompilerProvider only
      MapDefaultValue.php                 # InputMapperCompilerProvider only
      ValidatedMapperCompiler.php         # InputMapperCompilerProvider only
      Optional.php                        # MOVED (not a compiler provider, consumed by factory)
      # Metadata attributes (not compiler providers):
      SourceKey.php                       # MOVED
      AllowExtraKeys.php                  # MOVED
      Discriminator.php                   # MOVED

    Mapper/
      MapperCompiler.php                  # UNCHANGED — unified interface for both directions
      GenericMapperCompiler.php           # UNCHANGED
      UndefinedAwareMapperCompiler.php    # UNCHANGED (input-only concept, but valid MapperCompiler)
      MapRuntime.php                      # UNCHANGED
      PassthroughMapperCompiler.php       # NEW — accepts any TypeNode, returns $value unchanged

      Input/                              # Compiler implementations for input direction
        IntInputMapperCompiler.php        # NEW — extracted from old MapInt::compile()
        StringInputMapperCompiler.php
        BoolInputMapperCompiler.php
        FloatInputMapperCompiler.php
        MixedInputMapperCompiler.php
        ObjectInputMapperCompiler.php     # Extracted from old MapObject::compile()
        EnumInputMapperCompiler.php
        DateTimeImmutableInputMapperCompiler.php
        DiscriminatedObjectInputMapperCompiler.php
        DelegateInputMapperCompiler.php   # REFACTORED from DelegateMapperCompiler
        ListInputMapperCompiler.php
        ArrayInputMapperCompiler.php
        ArrayShapeInputMapperCompiler.php
        NullableInputMapperCompiler.php
        OptionalInputMapperCompiler.php
        DefaultValueInputMapperCompiler.php
        ValidatedInputMapperCompiler.php
        ChainMapperCompiler.php           # MOVED — already direction-agnostic

      Output/                             # NEW — compiler implementations for output direction
        ObjectOutputMapperCompiler.php
        EnumOutputMapperCompiler.php
        DateTimeImmutableOutputMapperCompiler.php
        DiscriminatedObjectOutputMapperCompiler.php
        DelegateOutputMapperCompiler.php
        ListOutputMapperCompiler.php
        ArrayOutputMapperCompiler.php
        ArrayShapeOutputMapperCompiler.php
        NullableOutputMapperCompiler.php
        OptionalOutputMapperCompiler.php

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

### Step 1: Move attributes to `src/Compiler/Attribute/` (BC break, no new functionality)
- Move all attributes from `src/Compiler/Mapper/{Array,Object,Scalar,Wrapper,Mixed}/` to `src/Compiler/Attribute/`
- Update namespaces and all references
- All existing tests should still pass

### Step 2: Attribute refactoring (BC break, no new functionality)
- Create `InputMapperCompilerProvider` and `OutputMapperCompilerProvider` interfaces in `src/Compiler/Attribute/`
- For each existing attribute that implements `MapperCompiler`:
  - Extract `compile()`, `getInputType()`, `getOutputType()` into a new `{Kind}InputMapperCompiler` class under `src/Compiler/Mapper/Input/`
  - Make the attribute implement `InputMapperCompilerProvider` returning the extracted compiler
  - Remove `MapperCompiler` implementation from the attribute
- Update `DefaultMapperCompilerFactory` to discover attributes via `InputMapperCompilerProvider` instead of `MapperCompiler`
- All existing tests should still pass (same behavior, different wiring)

### Step 3: Rename runtime interfaces (BC break, no new functionality)
- `Mapper` → `InputMapper`, `MapperProvider` → `InputMapperProvider`, etc.
- `DefaultMapperCompilerFactory` → `DefaultInputMapperCompilerFactory`, etc.

### Step 4: Core output runtime
- `OutputMapper<T>` interface with `map()` method
- `OutputMapperProvider` class (compilation + caching infrastructure)
- `PhpCodeBuilder` extensions (`outputMapperMethod`, `outputMapperClass`, `outputMapperFile`)
- `CallbackOutputMapper`

### Step 5: Scalar output + PassthroughMapperCompiler
- `PassthroughMapperCompiler` (covers int, string, bool, float, mixed — accepts any `TypeNode`)
- Add `OutputMapperCompilerProvider` to `MapInt`, `MapString`, `MapBool`, `MapFloat`, `MapMixed`
- End-to-end test: compile + run an output mapper for a flat scalar-only object

### Step 6: Object output compiler
- `ObjectOutputMapperCompiler` — the most important and complex piece (public readonly promoted properties only)
- `DelegateOutputMapperCompiler` — for nested object references
- Add `OutputMapperCompilerProvider` to `MapObject`

### Step 7: Wrapper output compilers
- `NullableOutputMapperCompiler`
- `OptionalOutputMapperCompiler`
- Add `OutputMapperCompilerProvider` to `MapNullable`

### Step 8: Collection output compilers
- `ListOutputMapperCompiler`
- `ArrayOutputMapperCompiler`
- `ArrayShapeOutputMapperCompiler`
- Add `OutputMapperCompilerProvider` to `MapList`, `MapArray`, `MapArrayShape`

### Step 9: Special type output compilers
- `EnumOutputMapperCompiler` + add `OutputMapperCompilerProvider` to `MapEnum`
- `DateTimeImmutableOutputMapperCompiler` + add `OutputMapperCompilerProvider` to `MapDateTimeImmutable`

### Step 10: Discriminated object output
- `DiscriminatedObjectOutputMapperCompiler` + add `OutputMapperCompilerProvider` to `MapDiscriminatedObject`

### Step 11: Output compiler factory
- `DefaultOutputMapperCompilerFactory` — auto-creates output `MapperCompiler` trees from class reflection
- `OutputMapperCompilerFactoryProvider` + `DefaultOutputMapperCompilerFactoryProvider`

### Step 12: Tests
- Unit tests mirroring existing input mapper tests
- Round-trip tests: `map_output(map_input($data)) === $data` for various type combinations
- Tests for edge cases: optional fields, discriminated objects, generics, date formats

### Step 13: PHPStan extensions
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

// Input mapping (renamed interface)
$inputMapper = $inputMapperProvider->get(PersonInput::class);
$person = $inputMapper->map(['id' => 1, 'full_name' => 'John', 'email' => 'john@example.com']);

// Output mapping (new)
$outputMapper = $outputMapperProvider->get(PersonInput::class);
$array = $outputMapper->map($person);
// Result: ['id' => 1, 'full_name' => 'John', 'email' => 'john@example.com']
$json = json_encode($array);
```

---

## 14. Open Questions

1. **Property access strategy (future)**: When should we add support for getters / non-promoted properties? Not needed initially, but good to design for extensibility.
2. **Output-specific validation**: Should there be any output-side validation? (e.g., "assert this value is non-null before serializing"). Probably not needed initially.
3. **Naming convention for extracted input compilers**: The plan uses `{Kind}InputMapperCompiler` (e.g. `IntInputMapperCompiler`). Alternatives: keep the `Map*` prefix for input (e.g. `MapIntCompiler`).
