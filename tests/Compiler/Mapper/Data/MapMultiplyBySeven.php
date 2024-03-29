<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Data;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapRuntime;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function is_int;

class MapMultiplyBySeven extends MapRuntime
{

    public static function mapValue(mixed $value, array $path): int
    {
        if (!is_int($value)) {
            throw MappingFailedException::incorrectType($value, $path, 'int');
        }

        return $value * 7;
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('mixed');
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

}
