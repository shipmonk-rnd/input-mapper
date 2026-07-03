<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Type\Data;

/**
 * Generic type with template defaults chained through other defaulted parameters.
 *
 * @template A
 * @template B = A
 * @template C = B
 */
interface ChainedDefaultsType
{

}
