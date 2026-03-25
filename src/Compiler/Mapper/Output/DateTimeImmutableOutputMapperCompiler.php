<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Output;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use function is_array;

class DateTimeImmutableOutputMapperCompiler implements MapperCompiler
{

    /**
     * @param string|non-empty-list<string> $format output format (if multiple formats were accepted on input, the first one is used for output)
     * @param ?string $targetTimezone timezone to convert to before formatting
     */
    public function __construct(
        public readonly string|array $format = DateTimeInterface::RFC3339,
        public readonly ?string $targetTimezone = null,
    )
    {
    }

    public function compile(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): CompiledExpr
    {
        $outputExpr = $value;

        if ($this->targetTimezone !== null) {
            $outputExpr = $builder->methodCall($value, 'setTimezone', [
                $builder->new($builder->importClass(DateTimeZone::class), [$this->targetTimezone]),
            ]);
        }

        $outputFormat = is_array($this->format) ? $this->format[0] : $this->format;

        return new CompiledExpr(
            $builder->methodCall($outputExpr, 'format', [$outputFormat]),
        );
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode(DateTimeImmutable::class);
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode('string');
    }

}
