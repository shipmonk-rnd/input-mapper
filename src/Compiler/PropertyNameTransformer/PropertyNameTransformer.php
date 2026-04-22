<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\PropertyNameTransformer;

interface PropertyNameTransformer
{

    /**
     * @param class-string $className FQCN of the class being compiled (subclass in case of inheritance)
     */
    public function transform(
        string $propertyName,
        string $className,
    ): string;

}
