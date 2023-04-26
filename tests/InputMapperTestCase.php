<?php declare(strict_types = 1);

namespace ShipMonkTests\InputMapper;

use Nette\Utils\FileSystem;
use PHPUnit\Framework\Constraint\Exception as ExceptionConstraint;
use PHPUnit\Framework\TestCase;
use Throwable;
use function getenv;
use function is_file;

abstract class InputMapperTestCase extends TestCase
{

    protected static function assertSnapshot(string $snapshotPath, string $actual): void
    {
        if (is_file($snapshotPath) && getenv('UPDATE_SNAPSHOTS') === false) {
            $expected = FileSystem::read($snapshotPath);
            self::assertSame($expected, $actual);
        } elseif (getenv('CI') === false) {
            FileSystem::write($snapshotPath, $actual);
        } else {
            self::fail("Snapshot file {$snapshotPath} does not exist. Run tests locally to generate it.");
        }
    }

    /**
     * @template T of Throwable
     * @param  class-string<T>   $type
     * @param  callable(): mixed $cb
     */
    protected static function assertException(string $type, ?string $message, callable $cb): void
    {
        try {
            $cb();
            self::assertThat(null, new ExceptionConstraint($type));
        } catch (Throwable $e) {
            self::assertThat($e, new ExceptionConstraint($type));

            if ($message !== null) {
                self::assertSame($message, $e->getMessage());
            }
        }
    }

}
