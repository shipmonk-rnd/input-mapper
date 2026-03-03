<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\OutputMapper;
use ShipMonk\InputMapper\Runtime\OutputMapperProvider;

/**
 * Generated mapper by {@see DateTimeImmutableOutputMapperCompiler}. Do not edit directly.
 *
 * @implements OutputMapper<DateTimeImmutable>
 */
class DateTimeImmutableMultiFormatOutputMapper implements OutputMapper
{
    public function __construct(private readonly OutputMapperProvider $provider)
    {
    }

    /**
     * @param  DateTimeImmutable $data
     * @param  list<string|int> $path
     * @return string
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data->format('Y-m-d\\TH:i:sP');
    }
}
