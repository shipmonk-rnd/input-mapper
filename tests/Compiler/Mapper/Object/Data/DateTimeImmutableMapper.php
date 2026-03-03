<?php declare (strict_types=1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Object\Data;

use DateTimeImmutable;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DateTimeImmutableInputMapperCompiler;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\InputMapper;
use ShipMonk\InputMapper\Runtime\InputMapperProvider;
use function is_string;

/**
 * Generated mapper by {@see DateTimeImmutableInputMapperCompiler}. Do not edit directly.
 *
 * @implements InputMapper<DateTimeImmutable>
 */
class DateTimeImmutableMapper implements InputMapper
{
    public function __construct(private readonly InputMapperProvider $provider)
    {
    }

    /**
     * @param  list<string|int> $path
     * @throws MappingFailedException
     */
    public function map(mixed $data, array $path = []): DateTimeImmutable
    {
        if (!is_string($data)) {
            throw MappingFailedException::incorrectType($data, $path, 'string');
        }

        $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:sP', $data);

        if ($mapped === false) {
            $mapped = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i:s.vP', $data);
        }

        if ($mapped === false) {
            throw MappingFailedException::incorrectValue($data, $path, 'date-time string in RFC 3339 format');
        }

        return $mapped;
    }
}
