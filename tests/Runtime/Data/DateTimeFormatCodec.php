<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;
use ShipMonk\InputMapper\Compiler\Attribute\MapCodec;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\MapperFactory\MapperCompilerFactory;
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;

/**
 * Codec usable directly as an attribute and composable with other mapper attributes,
 * e.g. `#[MapNullable(new DateTimeFormatCodec('Y-m-d'))]`.
 *
 * @implements Codec<string, DateTimeInterface, DateTimeImmutable, string>
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class DateTimeFormatCodec implements Codec, MapperCompilerProvider
{

    public function __construct(
        public readonly string $format = DateTimeInterface::ATOM,
    )
    {
    }

    /**
     * @param list<string|int> $path
     * @throws MappingFailedException
     */
    public function decode(mixed $data, array $path = []): DateTimeImmutable
    {
        $result = DateTimeImmutable::createFromFormat($this->format, $data);

        if ($result === false) {
            throw MappingFailedException::incorrectValue($data, $path, "date-time string in format '{$this->format}'");
        }

        return $result;
    }

    /**
     * @param list<string|int> $path
     */
    public function encode(mixed $data, array $path = []): string
    {
        return $data->format($this->format);
    }

    public function getInputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return (new MapCodec($this))->getInputMapperCompiler($mapperCompilerFactory, $options);
    }

    public function getOutputMapperCompiler(
        MapperCompilerFactory $mapperCompilerFactory,
        array $options,
    ): MapperCompiler
    {
        return (new MapCodec($this))->getOutputMapperCompiler($mapperCompilerFactory, $options);
    }

}
