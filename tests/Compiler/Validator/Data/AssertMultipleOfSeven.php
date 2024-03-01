<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Data;

use ShipMonk\InputMapper\Compiler\Validator\AssertRuntime;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;
use function is_int;

class AssertMultipleOfSeven extends AssertRuntime
{

    /**
     * @throws MappingFailedException
     */
    public static function assertValue(mixed $value, ?MapperContext $context = null): void
    {
        if (is_int($value) && $value % 7 !== 0) {
            throw MappingFailedException::incorrectValue($value, $context, 'multiple of 7');
        }
    }

}
