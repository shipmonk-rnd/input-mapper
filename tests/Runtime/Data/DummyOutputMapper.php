<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Runtime\OutputMapper;

/**
 * @implements OutputMapper<mixed>
 */
class DummyOutputMapper implements OutputMapper
{

    /**
     * @param list<string | int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data;
    }

}
