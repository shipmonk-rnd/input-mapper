<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Codec\Data;

use Attribute;
use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Mapper\InputMapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Runtime\Codec;
use function strrev;

/**
 * Codec implementing only the input half of the provider SPI, used to test
 * that it still maps through MapCodec on both sides instead of being silently dropped.
 *
 * @implements Codec<string, string, string, string>
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class InputOnlyProviderCodec implements Codec, InputMapperCompilerProvider
{

    /**
     * @param list<string|int> $path
     */
    public function decode(mixed $data, array $path = []): string
    {
        return strrev($data);
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return strrev($data);
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return (new MapCodec($this))->getInputMapperCompiler($mapperCompilerFactory, $options);
    }

}
