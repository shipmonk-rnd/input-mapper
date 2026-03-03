<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Runtime\CallbackOutputMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class CallbackOutputMapperTest extends InputMapperTestCase
{

    public function testMapOk(): void
    {
        $mapper = new CallbackOutputMapper(static fn (mixed $data) => ['value' => $data]);
        self::assertSame(['value' => 123], $mapper->map(123));
    }

    public function testMapThrowsException(): void
    {
        $mapper = new CallbackOutputMapper(static function (): never {
            throw MappingFailedException::incorrectValue('123', [], 'int');
        });

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "123"',
            static fn () => $mapper->map('123'),
        );
    }

}
