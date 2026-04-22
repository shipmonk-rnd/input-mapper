<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\PropertyNameTransformer;

use LogicException;
use function preg_replace;
use function strtolower;

class CamelToSnakeCasePropertyNameTransformer implements PropertyNameTransformer
{

    public function transform(
        string $propertyName,
        string $className,
    ): string
    {
        $result = preg_replace('/(?<=[a-z0-9])([A-Z])|(?<=[A-Z])([A-Z][a-z])/', '_$1$2', $propertyName);

        if ($result === null) {
            throw new LogicException('Failed to transform property name: ' . $propertyName);
        }

        return strtolower($result);
    }

}
