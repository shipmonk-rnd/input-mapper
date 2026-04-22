<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\PropertyNameTransformer;

interface PropertyNameTransformer
{

    public function transform(string $propertyName): string;

}
