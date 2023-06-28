<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper\Object;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class MapDate extends MapDateTimeImmutable
{

    public function __construct(
        string $formatDescription = 'date string in Y-m-d format',
        ?string $timezone = null,
    )
    {
        parent::__construct('!Y-m-d', $formatDescription, $timezone);
    }

}
