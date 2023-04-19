<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use LogicException;
use ShipMonk\InputMapper\Runtime\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class OptionalNoneTest extends InputMapperTestCase
{

    public function testIsDefined(): void
    {
        self::assertFalse(Optional::none([], 'key')->isDefined());
    }

    public function testGet(): void
    {
        self::assertException(
            LogicException::class,
            'Optional is not defined',
            static function (): void {
                Optional::none([], 'key')->get();
            },
        );
    }

    public function testRequire(): void
    {
        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /: Missing required key "key"',
            static function (): void {
                Optional::none([], 'key')->require();
            },
        );

        self::assertException(
            MappingFailedException::class,
            'Failed to map data at path /foo: Missing required key "bar"',
            static function (): void {
                Optional::none(['foo'], 'bar')->require();
            },
        );
    }

    public function testGetOrElse(): void
    {
        self::assertSame('default', Optional::none([], 'key')->getOrElse('default'));
    }

}
