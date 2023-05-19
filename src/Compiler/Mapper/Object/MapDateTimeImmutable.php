<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PhpParser\Node\Expr;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\CompiledExpr;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function count;
use function is_array;
use function is_string;

#[Attribute(Attribute::TARGET_PARAMETER)]
class MapDateTimeImmutable implements MapperCompiler
{

    /**
     * @param string|non-empty-list<string> $format
     */
    public function __construct(
        public readonly string|array $format = [DateTimeInterface::RFC3339, DateTimeInterface::RFC3339_EXTENDED],
        public readonly string $formatDescription = 'date-time string in RFC 3339 format',
        public readonly ?string $defaultTimezone = null,
        public readonly ?string $targetTimezone = null,
    )
    {
    }

    public function compile(Expr $value, Expr $path, PhpCodeBuilder $builder): CompiledExpr
    {
        $mappedVariableName = $builder->uniqVariableName('mapped');
        $timezoneVariableName = $builder->uniqVariableName('timezone');

        if ($this->defaultTimezone === null) {
            $timezoneArgs = [];
            $timezoneInitStatements = [];

        } else {
            $timezoneArgs = [$builder->var($timezoneVariableName)];
            $timezoneInitStatements = [
                $builder->assign(
                    $builder->var($timezoneVariableName),
                    $builder->new($builder->importClass(DateTimeZone::class), [$this->defaultTimezone]),
                ),
            ];
        }

        $statements = [
            $builder->if($builder->not($builder->funcCall($builder->importFunction('is_string'), [$value])), [
                $builder->throw(
                    $builder->staticCall(
                        $builder->importClass(MappingFailedException::class),
                        'incorrectType',
                        [$value, $path, $builder->val('string')],
                    ),
                ),
            ]),

            ...$timezoneInitStatements,

            $builder->assign(
                $builder->var($mappedVariableName),
                $builder->staticCall($builder->importClass(DateTimeImmutable::class), 'createFromFormat', [
                    is_string($this->format) ? $this->format : $this->format[0],
                    $value,
                    ...$timezoneArgs,
                ]),
            ),
        ];

        if (is_array($this->format)) {
            for ($i = 1; $i < count($this->format); $i++) {
                $statements[] = $builder->if($builder->same($builder->var($mappedVariableName), $builder->val(false)), [
                    $builder->assign(
                        $builder->var($mappedVariableName),
                        $builder->staticCall($builder->importClass(DateTimeImmutable::class), 'createFromFormat', [
                            $this->format[$i],
                            $value,
                            ...$timezoneArgs,
                        ]),
                    ),
                ]);
            }
        }

        $statements[] = $builder->if($builder->same($builder->var($mappedVariableName), $builder->val(false)), [
            $builder->throw(
                $builder->staticCall(
                    $builder->importClass(MappingFailedException::class),
                    'incorrectValue',
                    [$value, $path, $this->formatDescription],
                ),
            ),
        ]);

        if ($this->targetTimezone !== null) {
            $targetTimezone = $this->targetTimezone === $this->defaultTimezone
                ? $builder->var($timezoneVariableName)
                : $builder->new($builder->importClass(DateTimeZone::class), [$this->targetTimezone]);

            $statements[] = $builder->assign(
                $builder->var($mappedVariableName),
                $builder->methodCall($builder->var($mappedVariableName), 'setTimezone', [$targetTimezone]),
            );
        }

        return new CompiledExpr($builder->var($mappedVariableName), $statements);
    }

    public function getInputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(PhpCodeBuilder $builder): TypeNode
    {
        return new IdentifierTypeNode($builder->importClass(DateTimeImmutable::class));
    }

}
