<?php declare (strict_types=1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use function is_int;

/**
 * Generated mapper. Do not edit directly.
 *
 * @implements Mapper<Optional<int>>
 */
class OptionalIntMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @return Optional<int>
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): Optional
    {
        if (!is_int($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'int');
        }

        return Optional::of($data);
    }
}
