<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use DateTimeImmutable;
use DateTimeZone;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperProvider;

/**
 * Generated mapper by {@see DateTimeImmutableOutputMapperCompiler}. Do not edit directly.
 *
 * @implements Mapper<DateTimeImmutable, mixed>
 */
class DateTimeImmutableWithTimezoneOutputMapper implements Mapper
{
    public function __construct(private readonly MapperProvider $provider)
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
        return $data->setTimezone(new DateTimeZone('Europe/Prague'))->format('Y-m-d\\TH:i:sP');
    }
}
