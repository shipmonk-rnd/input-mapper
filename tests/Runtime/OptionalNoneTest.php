<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use LogicException;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\MapperContext;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class OptionalNoneTest extends InputMapperTestCase
{

    public function testIsDefined(): void
    {
        self::assertFalse(Optional::none(null, 'key')->isDefined());
    }

    public function testGet(): void
    {
        self::assertException(
            LogicException::class,
            'Optional is not defined',
            static function (): void {
                Optional::none(null, 'key')->get();
            },
        );
    }

    public function testRequire(): void
    {
        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "key"',
            static function (): void {
                Optional::none(null, 'key')->require();
            },
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /foo: Missing required key "bar"',
            static function (): void {
                Optional::none(MapperContext::fromPath(['foo']), 'bar')->require();
            },
        );
    }

    public function testGetOrElse(): void
    {
        // @phpstan-ignore-next-line always true
        self::assertSame('default', Optional::none(null, 'key')->getOrElse('default'));
    }

}
