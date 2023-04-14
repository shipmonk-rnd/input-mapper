<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator\String;

use Attribute;
use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use ShipMonk\InputMapper\Compiler\Php\PhpCodeBuilder;
use ShipMonk\InputMapper\Compiler\Validator\ValidatorCompiler;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function count;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AssertStringLength implements ValidatorCompiler
{

    private ?int $min;

    private ?int $max;

    public function __construct(
        ?int $min = null,
        ?int $max = null,
        ?int $exact = null,
    )
    {
        if ($exact === null) {
            $this->min = $min;
            $this->max = $max;
        } elseif ($min === null && $max === null) {
            $this->min = $exact;
            $this->max = $exact;
        } else {
            throw new LogicException('Cannot use exact with min/max');
        }
    }

    /**
     * @return list<Stmt>
     */
    public function compileValidator(
        Expr $value,
        Expr $path,
        PhpCodeBuilder $builder,
    ): array
    {
        $statements = [];
        $length = $builder->funcCall($builder->importFunction('strlen'), [$value]);

        if ($this->min !== null && $this->max !== null && $this->min === $this->max) {
            return [
                $builder->if($builder->notSame($length, $builder->val($this->min)), [
                    $builder->throwNew($builder->importClass(MappingFailedException::class), [
                        $value,
                        $path,
                        $builder->val("string with exactly {$this->min} characters"),
                    ]),
                ]),
            ];
        }

        if ($this->min !== null) {
            $statements[] = $builder->if($builder->lt($length, $builder->val($this->min)), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val("string with at least {$this->min} characters"),
                ]),
            ]);
        }

        if ($this->max !== null) {
            $statements[] = $builder->if($builder->gt($length, $builder->val($this->max)), [
                $builder->throwNew($builder->importClass(MappingFailedException::class), [
                    $value,
                    $path,
                    $builder->val("string with at most {$this->max} characters"),
                ]),
            ]);
        }

        if (count($statements) > 0) {
            $isString = $builder->funcCall($builder->importFunction('is_string'), [$value]);
            $statements = [$builder->if($isString, $statements)];
        }

        return $statements;
    }

    /**
     * @param  array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public function toJsonSchema(array $schema): array
    {
        if ($this->min !== null) {
            $schema['minLength'] = $this->min;
        }

        if ($this->max !== null) {
            $schema['maxLength'] = $this->max;
        }

        return $schema;
    }

}
