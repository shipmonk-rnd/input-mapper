<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Validator\Data;

use ShipMonk\InputMapper\Compiler\Validator\AssertRuntime;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use function is_int;

class AssertMultipleOfSeven extends AssertRuntime
{

    /**
     * @param  list<int|string> $path
     * @throws MappingFailedException
     */
    public static function assertValue(mixed $value, array $path): void
    {
        if (is_int($value) && $value % 7 !== 0) {
            throw MappingFailedException::incorrectValue($value, $path, 'multiple of 7');
        }
    }

    public function toJsonSchema(array $schema): array
    {
        return [
            'multipleOf' => 7,
        ];
    }

}
