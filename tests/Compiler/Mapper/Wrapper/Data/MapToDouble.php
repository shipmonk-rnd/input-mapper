<?php declare (strict_types = 1);

namespace ShipMonkTests\InputMapper\Compiler\Mapper\Wrapper\Data;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapRuntime;
use function assert;
use function is_int;

class MapToDouble extends MapRuntime
{

    public static function mapValue(mixed $value, array $path): int
    {
        assert(is_int($value));
        return $value * 2;
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode('int');
    }

}
