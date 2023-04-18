<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper\Runtime;

use LogicException;
use ShipMonk\InputMapper\Runtime\Optional;
use ShipMonkTests\InputMapper\InputMapperTestCase;

class OptionalNoneTest extends InputMapperTestCase
{

    public function testIsDefined(): void
    {
        self::assertFalse(Optional::none()->isDefined());
    }

    public function testGet(): void
    {
        self::assertException(
            LogicException::class,
            'Optional is not defined',
            static function (): void {
                Optional::none()->get();
            },
        );
    }

    public function testGetOrElse(): void
    {
        self::assertSame('default', Optional::none()->getOrElse('default'));
    }

}
