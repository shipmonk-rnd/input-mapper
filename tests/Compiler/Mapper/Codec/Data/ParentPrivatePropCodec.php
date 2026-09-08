<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

/**
 * Codec inheriting a constructor promoting a private property on the parent class,
 * used to test that inline compilation reads private parent properties.
 */
class ParentPrivatePropCodec extends BasePrivatePropCodec
{

}
