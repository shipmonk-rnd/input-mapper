<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\EnumInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function array_column;
use function implode;
use function is_string;

/**
 * Generated mapper by {@see EnumInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<SuitEnum>
 */
class DelegateToEnumCollection__SuitEnumMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
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
