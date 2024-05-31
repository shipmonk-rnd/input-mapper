<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Wrapper;

use Attribute;
use BackedEnum;
use LogicException;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\UndefinedAwareMapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Type\PhpDocTypeUtils;
use function array_is_list;
use function array_keys;
use function array_map;
use function array_values;
use function get_debug_type;
use function is_array;
use function is_scalar;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDefaultValue implements UndefinedAwareMapperCompiler
{

    public function __construct(
        public readonly MapperCompiler $mapperCompiler,
        public readonly mixed $defaultValue,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        return $this->mapperCompiler->compile($value, $path, $builder);
    }

    public function compileUndefined(Expr $path, Expr $key, PhpCodeBuilder $builder): CompiledExpr
    {
        if ($this->defaultValue === null || is_scalar($this->defaultValue) || is_array($this->defaultValue)) {
            return new CompiledExpr($builder->val(null));
        }

        if ($this->defaultValue instanceof BackedEnum) {
            return new CompiledExpr($builder->classConstFetch($builder->importClass($this->defaultValue::class), $this->defaultValue->name));
        }

        throw new LogicException('Unsupported default value type: ' . get_debug_type($this->defaultValue));
    }

    public function getInputType(): TypeNode
    {
        return $this->mapperCompiler->getInputType();
    }

    public function getOutputType(): TypeNode
    {
        return $this->mapperCompiler->getOutputType();
    }

    public function getDefaultValueType(): TypeNode
    {
        return $this->typeFromValue($this->defaultValue);
    }

    private function typeFromValue(mixed $value): TypeNode
    {
        if (is_scalar($value) || $value === null) {
            return new IdentifierTypeNode(get_debug_type($value));
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $valueType = PhpDocTypeUtils::union(...array_map($this->typeFromValue(...), $value));
                return new GenericTypeNode(new IdentifierTypeNode('list'), [$valueType]);
            }

            $keyType = PhpDocTypeUtils::union(...array_map($this->typeFromValue(...), array_keys($value)));
            $valueType = PhpDocTypeUtils::union(...array_map($this->typeFromValue(...), array_values($value)));
            return new GenericTypeNode(new IdentifierTypeNode('array'), [$keyType, $valueType]);
        }

        if ($value instanceof BackedEnum) {
            return new IdentifierTypeNode($value::class);
        }

        throw new LogicException('Unsupported default value type: ' . get_debug_type($value));
    }

}
