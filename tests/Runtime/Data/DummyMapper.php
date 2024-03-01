<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime\Data;

use ShipMonk\InputMapper\Runtime\Mapper;
use ShipMonk\InputMapper\Runtime\MapperContext;

/**
 * @implements Mapper<mixed>
 */
class DummyMapper implements Mapper
{

    public function map(mixed $data, ?MapperContext $context = null): mixed
    {
        return $data;
    }

}
