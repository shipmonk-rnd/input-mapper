<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use ShipMonk\InputMapper\Runtime\CallbackMapper;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapperTests\InputMapperTestCase;

class CallbackMapperTest extends InputMapperTestCase
{

    public function testMapOk(): void
    {
        // @phpstan-ignore-next-line allow casting to int
        $mapper = new CallbackMapper(static fn (mixed $data, array $path) => (int) $data);
        self::assertSame(123, $mapper->map('123'));
    }

    public function testMapThrowsException(): void
    {
        $mapper = new CallbackMapper(static function (): never {
            throw MappingFailedException::incorrectValue('123', [], 'int');
        });

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Expected int, got "123"',
            static fn () => $mapper->map('123'),
        );
    }

    public function testOutputMapOk(): void
    {
        $mapper = new CallbackMapper(static fn (mixed $data) => ['value' => $data]);
        self::assertSame(['value' => 123], $mapper->map(123));
    }

}
