<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Attribute;

use Attribute;
use DateTimeInterface;
use ShipMonk\InputMapper\Compiler\Mapper\Input\DateTimeImmutableInputMapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompiler;
use ShipMonk\InputMapper\Compiler\Mapper\MapperCompilerProvider;
use ShipMonk\InputMapper\Compiler\Mapper\Output\DateTimeImmutableOutputMapperCompiler;
use function is_array;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDateTimeImmutable implements MapperCompilerProvider
{

    /**
     * @param string|non-empty-list<string> $format if multiple formats are provided, they will be tried in sequence as provided
     * @param ?string $defaultTimezone timezone used when timezone in not explicitly specified in input value
     * @param ?string $targetTimezone timezone to which the result is converted (regardless of the whether timezone was specified in input)
     */
    public function __construct(
        public readonly string|array $format = [DateTimeInterface::RFC3339, DateTimeInterface::RFC3339_EXTENDED],
        public readonly string $formatDescription = 'date-time string in RFC 3339 format',
        public readonly ?string $defaultTimezone = null,
        public readonly ?string $targetTimezone = null,
    )
    {
    }

    public function getInputMapperCompiler(): MapperCompiler
    {
        return new DateTimeImmutableInputMapperCompiler($this->format, $this->formatDescription, $this->defaultTimezone, $this->targetTimezone);
    }

    public function getOutputMapperCompiler(): MapperCompiler
    {
        $outputFormat = is_array($this->format) ? $this->format[0] : $this->format;
        return new DateTimeImmutableOutputMapperCompiler($outputFormat, $this->targetTimezone);
    }

}
