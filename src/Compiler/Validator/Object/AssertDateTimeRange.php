<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\Object;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Throw_;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class AssertDateTimeRange implements ValidatorCompiler
{

    public function __construct(
        public readonly ?string $gte = null,
        public readonly ?string $gt = null,
        public readonly ?string $lt = null,
        public readonly ?string $lte = null,
        public readonly ?string $timezone = null,
    )
    {
    }

    /**
     * @return list<Stmt>
     */
    public function compile(
        Expr $value,
        TypeNode $type,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $timezoneVariableName = $builder->uniqVariableName('timezone');
        $statements = [];

        if ($this->timezone === null) {
            $timezoneArgs = [];
            $timezoneInitStatements = [];

        } else {
            $timezoneArgs = [$builder->var($timezoneVariableName)];
            $timezoneInitStatements = [
                $builder->assign(
                    $builder->var($timezoneVariableName),
                    $builder->new($builder->importClass(DateTimeZone::class), [$this->timezone]),
                ),
            ];
        }

        if ($this->gte !== null) {
            $boundary = $builder->new($builder->importClass(DateTimeImmutable::class), [$builder->val($this->gte), ...$timezoneArgs]);
            $statements[] = $builder->if($builder->lt($value, $boundary), [
                $this->throwException('greater than or equal to', $this->gte, $value, $path, $builder),
            ]);
        }

        if ($this->gt !== null) {
            $boundary = $builder->new($builder->importClass(DateTimeImmutable::class), [$builder->val($this->gt), ...$timezoneArgs]);
            $statements[] = $builder->if($builder->lte($value, $boundary), [
                $this->throwException('greater than', $this->gt, $value, $path, $builder),
            ]);
        }

        if ($this->lt !== null) {
            $boundary = $builder->new($builder->importClass(DateTimeImmutable::class), [$builder->val($this->lt), ...$timezoneArgs]);
            $statements[] = $builder->if($builder->gte($value, $boundary), [
                $this->throwException('less than', $this->lt, $value, $path, $builder),
            ]);
        }

        if ($this->lte !== null) {
            $boundary = $builder->new($builder->importClass(DateTimeImmutable::class), [$builder->val($this->lte), ...$timezoneArgs]);
            $statements[] = $builder->if($builder->gt($value, $boundary), [
                $this->throwException('less than or equal to', $this->lte, $value, $path, $builder),
            ]);
        }

        if (count($statements) > 0) {
            $statements = [
                ...$timezoneInitStatements,
                ...$statements,
            ];
        }

        return $statements;
    }

    private function throwException(
        string $boundaryDescription,
        string $boundaryValue,
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): Throw_
    {
        if ($this->timezone !== null) {
            $boundaryValue .= " (in {$this->timezone} timezone)";
        }

        return $builder->throw(
            $builder->staticCall(
                $builder->importClass(MappingFailedException::class),
                'incorrectValue',
                [$value, $path, $builder->val("value {$boundaryDescription} {$boundaryValue}")],
            ),
        );
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode(DateTimeInterface::class);
    }

}
