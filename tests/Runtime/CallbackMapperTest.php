<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\CallbackMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class CallbackMapperTest extends InputMapperTestCase
{

    public function testMapOk(): void
    {
        $mapper = new CallbackMapper(static fn (mixed $data, array $path) => (int) $data);
        self::assertSame(123, $mapper->map('123'));
    }

    public function testMapThrowsException(): void
    {
        $exception = MappingFailedException::incorrectValue('123', [], 'int');
        $mapper = new CallbackMapper(static fn (mixed $data, array $path) => throw $exception);

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "123"',
            static fn () => $mapper->map('123'),
        );
    }

}