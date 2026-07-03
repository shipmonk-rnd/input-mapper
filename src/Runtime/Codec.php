<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * Bidirectional type conversion between intermediate and domain types.
 * The library auto-compiles the mixed -> intermediate bridge; the user implements the intermediate -> domain conversion.
 *
 * @template DI The intermediate type (decode input)
 * @template EI The domain type (encode input)
 * @template DO = EI The domain type (decode output)
 * @template EO = DI The intermediate type (encode output)
 */
interface Codec
{

    /**
     * @param DI $data
     * @param list<string|int> $path
     * @return DO
     *
     * @throws MappingFailedException
     */
    public function decode(
        mixed $data,
        array $path = [],
    ): mixed;

    /**
     * @param EI $data
     * @param list<string|int> $path
     * @return EO
     *
     * @throws MappingFailedException
     */
    public function encode(
        mixed $data,
        array $path = [],
    ): mixed;

}
