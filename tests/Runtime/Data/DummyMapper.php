<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\InputMapper;

/**
 * @implements InputMapper<mixed>
 */
class DummyMapper implements InputMapper
{

    /**
     * @param list<string | int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data;
    }

}
