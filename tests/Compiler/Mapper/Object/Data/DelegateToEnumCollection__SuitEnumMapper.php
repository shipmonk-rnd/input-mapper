<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Object\MapEnum;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use function array_column;
use function implode;
use function is_string;

/**
 * Generated mapper by {@see MapEnum}. Do not edit directly.
 *
 * @implements Mapper<SuitEnum>
 */
class DelegateToEnumCollection__SuitEnumMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): SuitEnum
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $enum = SuitEnum::tryFrom($data);

        if ($enum === null) {
            throw MappingFailedException::incorrectValue($data, $path, 'one of ' . implode(', ', array_column(SuitEnum::cases(), 'value')));
        }

        return $enum;
    }
}
