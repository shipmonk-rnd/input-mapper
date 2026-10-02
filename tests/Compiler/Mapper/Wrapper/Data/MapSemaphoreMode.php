<?php declare (strict_types = 1);

namespace ShipMonk\InputMapperTests\Compiler\Mapper\Wrapper\Data;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use ShipMonk\InputMapper\Compiler\Mapper\MapRuntime;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use function is_string;

/**
 * A pure enum carries no backing value, so EnumInputMapperCompiler cannot map it.
 */
class MapSemaphoreMode extends MapRuntime
{

    /**
     * @param list<string|int> $path
     *
     * @throws MappingFailedException
     */
    public static function mapValue(mixed $value, array $path): SemaphoreModeEnum
    {
        if (!is_string($value)) {
            throw MappingFailedException::incorrectType($value, $path, 'string');
        }

        return match ($value) {
            'normal' => SemaphoreModeEnum::Normal,
            'blinking' => SemaphoreModeEnum::Blinking,
            default => throw MappingFailedException::incorrectValue($value, $path, "one of 'normal', 'blinking'"),
        };
    }

    public function getInputType(): TypeNode
    {
        return new IdentifierTypeNode('string');
    }

    public function getOutputType(): TypeNode
    {
        return new IdentifierTypeNode(SemaphoreModeEnum::class);
    }

}
