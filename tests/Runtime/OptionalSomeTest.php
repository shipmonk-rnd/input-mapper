<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class OptionalSomeTest extends InputMapperTestCase
{

    public function testIsDefined(): void
    {
        self::assertTrue(Optional::of(123)->isDefined());
        self::assertTrue(Optional::of(null)->isDefined());
    }

    public function testGet(): void
    {
        self::assertSame(123, Optional::of(123)->get());
        self::assertNull(Optional::of(null)->get()); // @phpstan-ignore-line always true
    }

    public function testRequire(): void
    {
        self::assertSame(123, Optional::of(123)->require());
        self::assertNull(Optional::of(null)->require()); // @phpstan-ignore-line always true
    }

    public function testGetOrElse(): void
    {
        self::assertSame(123, Optional::of(123)->getOrElse(456));
        self::assertNull(Optional::of(null)->getOrElse(456)); // @phpstan-ignore-line always true
    }

}
