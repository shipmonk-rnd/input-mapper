<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Compiler\Mapper\Input\OptionalInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapper\Runtime\OptionalSome;
use function is_int;

/**
 * Generated mapper by {@see OptionalInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<OptionalSome<int>>
 */
class OptionalIntMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return OptionalSome<int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): OptionalSome
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return Optional::of($data);
    }
}
