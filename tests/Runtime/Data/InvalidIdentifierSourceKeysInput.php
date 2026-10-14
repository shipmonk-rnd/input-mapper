<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime\Data;

use ShipMonk\InputMapper\Compiler\Attribute\SourceKey;
use ShipMonk\InputMapper\Runtime\Optional;

class InvalidIdentifierSourceKeysInput
{

    /**
     * @param Optional<string> $other
     * @param array{'bom-ref': int, 'x:y'?: string} $meta
     */
    public function __construct(
        #[SourceKey('bom-ref')]
        public readonly string $bomRef,
        #[SourceKey('x:y')]
        public readonly Optional $other,
        public readonly array $meta,
    )
    {
    }

}
