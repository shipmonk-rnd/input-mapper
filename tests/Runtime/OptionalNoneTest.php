<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use LogicException;
use ShipMonk\InputMapper\Runtime\Exception\MappingFailedException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonk\InputMapperTests\InputMapperTestCase;

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

        // @phpstan-ignore deadCode.unreachable (false positive)
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
        // @phpstan-ignore-next-line always true
        self::assertSame('default', Optional::none([], 'key')->getOrElse('default'));
    }

}
