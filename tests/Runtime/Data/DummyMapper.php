<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Mapper;

/**
 * @implements Mapper<mixed>
 */
class DummyMapper implements Mapper
{

    /**
     * @param list<string | int> $path
     */
    public function map(mixed $data, array $path = []): mixed
    {
        return $data;
    }

}
