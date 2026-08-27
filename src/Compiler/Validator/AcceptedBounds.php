<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Validator;

use function implode;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The interval of values that a bounded validator accepts.
 *
 * Bounds that accept nothing make the validator reject every input, so a bounded validator asks this object about its
 * own arguments and refuses to be built. Bounds that accept everything are a different matter: they are useless, not
 * wrong, and {@see Int\AssertIntRange} without arguments stays a supported no-op.
 */
final class AcceptedBounds
{

    private function __construct(
        private readonly int|float|null $lowerBound,
        private readonly int|float|null $upperBound,
        private readonly bool $lowerBoundIsInclusive,
        private readonly bool $upperBoundIsInclusive,
        private readonly string $arguments,
    )
    {
    }

    /**
     * Integers are discrete, so an exclusive bound shifts to the neighbouring integer and both bounds become
     * inclusive. That makes `gt: 1, lt: 2` empty, unlike the same bounds over a continuous domain.
     *
     * A bound at the integer limit keeps the clamping that {@see Int\AssertIntRange::getNarrowedType()} applies, so
     * `gt: PHP_INT_MAX` stays the documented no-op rather than becoming an error.
     */
    public static function overIntegers(
        ?int $gte = null,
        ?int $gt = null,
        ?int $lt = null,
        ?int $lte = null,
    ): self
    {
        return new self(
            lowerBound: self::tightest($gte, $gt === null || $gt === PHP_INT_MAX ? $gt : $gt + 1, keepGreater: true),
            upperBound: self::tightest($lte, $lt === null || $lt === PHP_INT_MIN ? $lt : $lt - 1, keepGreater: false),
            lowerBoundIsInclusive: true,
            upperBoundIsInclusive: true,
            arguments: self::renderArguments(['gte' => $gte, 'gt' => $gt, 'lt' => $lt, 'lte' => $lte]),
        );
    }

    /**
     * A length is a non-negative integer, so a negative upper bound accepts nothing even without a lower bound.
     */
    public static function overLengths(
        ?int $min = null,
        ?int $max = null,
    ): self
    {
        return new self(
            lowerBound: $min ?? 0,
            upperBound: $max,
            lowerBoundIsInclusive: true,
            upperBoundIsInclusive: true,
            arguments: self::renderArguments(['min' => $min, 'max' => $max]),
        );
    }

    /**
     * A continuous domain keeps values between two distinct bounds, so only equal or crossed bounds accept nothing.
     */
    public static function overFloats(
        ?float $gte = null,
        ?float $gt = null,
        ?float $lt = null,
        ?float $lte = null,
    ): self
    {
        return self::overContinuousValues(
            $gte,
            $gt,
            $lt,
            $lte,
            self::renderArguments(['gte' => $gte, 'gt' => $gt, 'lt' => $lt, 'lte' => $lte]),
        );
    }

    public function acceptNothing(): bool
    {
        if ($this->lowerBound === null || $this->upperBound === null) {
            return false;
        }

        $order = $this->lowerBound <=> $this->upperBound;

        if ($order > 0) {
            return true;
        }

        return $order === 0 && !($this->lowerBoundIsInclusive && $this->upperBoundIsInclusive);
    }

    /**
     * Names the arguments to fix, the way the author wrote them.
     */
    public function describeArguments(): string
    {
        return $this->arguments;
    }

    private static function overContinuousValues(
        ?float $gte,
        ?float $gt,
        ?float $lt,
        ?float $lte,
        string $arguments,
    ): self
    {
        return new self(
            lowerBound: self::tightest($gte, $gt, keepGreater: true),
            upperBound: self::tightest($lte, $lt, keepGreater: false),
            lowerBoundIsInclusive: $gte !== null && ($gt === null || $gte > $gt),
            upperBoundIsInclusive: $lte !== null && ($lt === null || $lte < $lt),
            arguments: $arguments,
        );
    }

    private static function tightest(
        int|float|null $left,
        int|float|null $right,
        bool $keepGreater,
    ): int|float|null
    {
        if ($left === null || $right === null) {
            return $left ?? $right;
        }

        $order = $left <=> $right;

        return ($keepGreater ? $order >= 0 : $order <= 0) ? $left : $right;
    }

    /**
     * @param array<string, int|float|null> $arguments
     */
    private static function renderArguments(array $arguments): string
    {
        $parts = [];

        foreach ($arguments as $name => $value) {
            if ($value !== null) {
                $parts[] = "{$name}: {$value}";
            }
        }

        return implode(', ', $parts);
    }

}
