<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime;

use RuntimeException;
use Throwable;
use function implode;
use function json_encode;
use function sprintf;

/**
 * @template T
 */
class MappingFailedException extends RuntimeException
{

    /**
     * @param list<string|int> $path
     * @param T                $data
     */
    public function __construct(mixed $data, array $path, string $expected = '', ?Throwable $previous = null)
    {
        $message = sprintf('Failed to map data at path /%s: expected %s, got %s', implode('/', $path), $expected, json_encode($data));
        parent::__construct($message, 0, $previous);
    }

}
