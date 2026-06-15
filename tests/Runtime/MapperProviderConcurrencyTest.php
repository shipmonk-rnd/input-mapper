<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperTests\Runtime;

use Nette\Utils\FileSystem;
use ShipMonk\InputMapperTests\InputMapperTestCase;
use ShipMonk\InputMapperTests\Runtime\Data\ConcurrencyTestInput;
use ShipMonk\InputMapperTests\Runtime\Data\LockWaitTestInput;
use function dirname;
use function fclose;
use function flock;
use function fopen;
use function getmypid;
use function glob;
use function implode;
use function is_resource;
use function md5;
use function microtime;
use function mkdir;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function reset;
use function stream_get_contents;
use function substr;
use function sys_get_temp_dir;
use function usleep;
use const LOCK_EX;
use const LOCK_UN;
use const PHP_BINARY;

class MapperProviderConcurrencyTest extends InputMapperTestCase
{

    /**
     * Several PHP-FPM-like workers race to compile and write the same mapper file for the first time.
     * The flock + write-tmp-then-rename in MapperProvider::load() must keep this safe: every worker
     * succeeds, exactly one valid mapper file results, and no half-written *.tmp file is left behind.
     */
    public function testParallelMapperGenerationIsSafe(): void
    {
        $tempDir = sys_get_temp_dir() . '/input-mapper-test-concurrency-' . getmypid();
        @mkdir($tempDir, recursive: true); // @ directory may already exist

        /** @var array<int, resource> $processes */
        $processes = [];

        /** @var array<int, array<int, resource>> $pipesByIndex */
        $pipesByIndex = [];

        try {
            $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
            $childScript = "$tempDir/child.php";
            FileSystem::write($childScript, self::childScript());

            $childCount = 4;

            // Spawn all children first so they genuinely race to compile the same class.
            for ($i = 0; $i < $childCount; $i++) {
                $pipes = [];
                $process = proc_open(
                    [PHP_BINARY, $childScript, $autoloadPath, $tempDir, ConcurrencyTestInput::class],
                    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                    $pipes,
                );

                self::assertNotFalse($process, "Failed to spawn child $i.");
                $processes[$i] = $process;
                $pipesByIndex[$i] = $pipes;
            }

            // Now collect each child's result.
            foreach ($processes as $i => $process) {
                $pipes = $pipesByIndex[$i];

                if (!self::waitForExit($process)) {
                    proc_terminate($process);
                    self::fail("Child $i did not finish in time — possible deadlock in MapperProvider::load().");
                }

                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                unset($pipesByIndex[$i]);
                $exitCode = proc_close($process);
                unset($processes[$i]);

                $stdout = $stdout === false ? '' : $stdout;
                $stderr = $stderr === false ? '' : $stderr;

                self::assertSame(0, $exitCode, "Child $i exited with code $exitCode. stderr: $stderr");
                self::assertSame('OK', $stdout, "Child $i did not report success. stderr: $stderr");
            }

            // Exactly one valid mapper file, and no leftover *.tmp from a half-finished write.
            $mapperFiles = glob("$tempDir/ConcurrencyTestInputMapper_*.php");
            self::assertIsArray($mapperFiles);
            self::assertCount(1, $mapperFiles);

            $tmpFiles = glob("$tempDir/*.tmp");
            self::assertIsArray($tmpFiles);
            self::assertCount(0, $tmpFiles);

            // The generated mapper must be syntactically valid PHP.
            $mapperFile = reset($mapperFiles);
            [$lintExit, $lintOutput] = self::runProcess([PHP_BINARY, '-l', $mapperFile]);
            self::assertSame(0, $lintExit, "php -l reported errors: $lintOutput");
        } finally {
            $this->cleanup($processes, $pipesByIndex, $tempDir);
        }
    }

    /**
     * A process that finds a foreign exclusive lock already held on the mapper file must wait for it
     * to be released before proceeding. The assertion direction is chosen so a slow child can only
     * make the test pass (still running), never false-fail.
     */
    public function testMapperGenerationWaitsForForeignLock(): void
    {
        $tempDir = sys_get_temp_dir() . '/input-mapper-test-lockwait-' . getmypid();
        @mkdir($tempDir, recursive: true); // @ directory may already exist

        $className = LockWaitTestInput::class;
        $shortName = 'LockWaitTestInput';
        $hash = substr(md5($className), 0, 8);
        $path = "$tempDir/{$shortName}Mapper_{$hash}.php";

        $lockHandle = null;
        $process = null;
        $pipes = [];

        try {
            // Parent grabs the exclusive lock a competing process must wait on.
            $lockHandle = fopen("$path.lock", 'c+');
            self::assertNotFalse($lockHandle);
            self::assertTrue(flock($lockHandle, LOCK_EX));

            $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
            $childScript = "$tempDir/child.php";
            FileSystem::write($childScript, self::childScript());

            $process = proc_open(
                [PHP_BINARY, $childScript, $autoloadPath, $tempDir, $className],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            self::assertNotFalse($process);

            // While the parent holds the lock, the child must stay blocked in flock(). Poll for ~1s.
            $running = true;

            for ($i = 0; $i < 20; $i++) {
                usleep(50_000);
                $running = proc_get_status($process)['running'];

                if (!$running) {
                    break;
                }
            }

            if (!$running) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                self::fail(
                    'Child completed while a foreign lock was held — locking is broken. '
                    . 'stdout: ' . ($stdout === false ? '' : $stdout) . ', '
                    . 'stderr: ' . ($stderr === false ? '' : $stderr),
                );
            }

            // Release the lock; the child should now acquire it, compile, and finish.
            self::assertTrue(flock($lockHandle, LOCK_UN));
            fclose($lockHandle);
            $lockHandle = null;

            if (!self::waitForExit($process)) {
                proc_terminate($process);
                self::fail('Child did not finish in time after lock release — possible deadlock in MapperProvider::load().');
            }

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $process = null;

            $stdout = $stdout === false ? '' : $stdout;
            $stderr = $stderr === false ? '' : $stderr;

            self::assertSame(0, $exitCode, "Child exited with code $exitCode. stderr: $stderr");
            self::assertSame('OK', $stdout, "Child did not report success. stderr: $stderr");

            $mapperFiles = glob("$tempDir/{$shortName}Mapper_*.php");
            self::assertIsArray($mapperFiles);
            self::assertCount(1, $mapperFiles);
        } finally {
            if (is_resource($lockHandle)) {
                @flock($lockHandle, LOCK_UN); // @ best-effort cleanup
                fclose($lockHandle);
            }

            $processes = is_resource($process) ? [$process] : [];
            $this->cleanup($processes, [$pipes], $tempDir);
        }
    }

    /**
     * The script run by each child process: load a mapper with autoRefresh=false (the locked path)
     * and map a trivial payload, printing OK on success or the error message to stderr with exit 1.
     */
    private static function childScript(): string
    {
        return <<<'PHP'
            <?php declare(strict_types = 1);

            require $argv[1];

            try {
                $provider = new ShipMonk\InputMapper\Runtime\MapperProvider($argv[2], autoRefresh: false);
                $mapper = $provider->getInputMapper($argv[3]);
                $mapper->map(['number' => 1]);
                echo 'OK';
            } catch (\Throwable $e) {
                fwrite(STDERR, $e->getMessage());
                exit(1);
            }
            PHP;
    }

    /**
     * @param list<string> $command
     * @return array{int, string} [exitCode, stdout and stderr combined]
     */
    private static function runProcess(array $command): array
    {
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertNotFalse($process, 'Failed to spawn: ' . implode(' ', $command));

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [$exitCode, ($stdout === false ? '' : $stdout) . ($stderr === false ? '' : $stderr)];
    }

    /**
     * Bounded watchdog: returns true once the process exits, or false if it is still running after
     * a generous timeout. Guards the blocking pipe reads so a regression that deadlocks a child
     * (e.g. broken locking) fails the test fast instead of hanging CI. A healthy child exits in well
     * under the timeout, so this can never cause a false failure on slow CI.
     *
     * @param resource $process
     */
    private static function waitForExit($process): bool
    {
        $deadline = microtime(true) + 30.0;

        while (proc_get_status($process)['running']) {
            if (microtime(true) >= $deadline) {
                return false;
            }

            usleep(10_000);
        }

        return true;
    }

    /**
     * @param array<int, resource> $processes
     * @param array<int, array<int, resource>> $pipesByIndex
     */
    private function cleanup(
        array $processes,
        array $pipesByIndex,
        string $tempDir,
    ): void
    {
        foreach ($pipesByIndex as $pipes) {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }

        foreach ($processes as $process) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }

        FileSystem::delete($tempDir);
    }

}
