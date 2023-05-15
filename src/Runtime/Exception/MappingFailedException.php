<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime\Exception;

use Throwable;
use function array_map;
use function array_slice;
use function count;
use function extension_loaded;
use function get_debug_type;
use function implode;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function strlen;
use function substr;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;

class MappingFailedException extends RuntimeException
{

    private const JSON_ENCODE_OPTIONS = JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;
    private const MAX_STRING_LENGTH = 40;

    /**
     * @param  list<string|int> $path
     */
    private function __construct(array $path, string $reason, ?Throwable $previous = null)
    {
        $jsonPointer = self::toJsonPointer($path);
        parent::__construct("Failed to map data at path {$jsonPointer}: {$reason}", $previous);
    }

    /**
     * @param  list<string|int> $path
     */
    public static function incorrectType(
        mixed $data,
        array $path,
        string $expectedType,
        ?Throwable $previous = null
    ): self
    {
        $describedValue = self::describeValue($data);
        $reason = "Expected {$expectedType}, got {$describedValue}";
        return new self($path, $reason, $previous);
    }

    /**
     * @param  list<string|int> $path
     */
    public static function incorrectValue(
        mixed $data,
        array $path,
        string $expectedValueDescription,
        ?Throwable $previous = null
    ): self
    {
        $describedValue = self::describeValue($data);
        $reason = "Expected {$expectedValueDescription}, got {$describedValue}";
        return new self($path, $reason, $previous);
    }

    /**
     * @param  list<string|int> $path
     */
    public static function missingKey(
        array $path,
        string $missingKey,
        ?Throwable $previous = null
    ): self
    {
        $missingKeyDescription = self::describeValue($missingKey);
        $reason = "Missing required key {$missingKeyDescription}";
        return new self($path, $reason, $previous);
    }

    /**
     * @param  list<string|int>           $path
     * @param  non-empty-list<string|int> $extraKeys
     */
    public static function extraKeys(
        array $path,
        array $extraKeys,
        ?Throwable $previous = null
    ): self
    {
        $keyLabel = count($extraKeys) > 1 ? 'keys' : 'key';
        $reason = "Unrecognized {$keyLabel} " . self::humanImplode(array_map(self::describeValue(...), $extraKeys));
        return new self($path, $reason, $previous);
    }

    /**
     * @param  non-empty-list<string> $items
     */
    private static function humanImplode(array $items): string
    {
        return count($items) > 1
            ? implode(', ', array_slice($items, 0, -1)) . ' and ' . $items[count($items) - 1]
            : $items[0];
    }

    /**
     * @param  list<string|int> $path
     */
    private static function toJsonPointer(array $path): string
    {
        return '/' . implode('/', $path);
    }

    private static function describeValue(mixed $value): string
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return json_encode($value, self::JSON_ENCODE_OPTIONS);
        }

        if (is_float($value)) {
            return is_finite($value)
                ? json_encode($value, self::JSON_ENCODE_OPTIONS)
                : (string) $value;
        }

        if (is_string($value)) {
            $printable = false;
            $truncated = false;

            if (extension_loaded('mbstring')) {
                if (preg_match('#^[^\p{C}]*+$#u', $value) === 1) {
                    $printable = true;
                    $truncated = mb_strlen($value, 'UTF-8') > self::MAX_STRING_LENGTH;
                    $value = $truncated ? mb_substr($value, 0, self::MAX_STRING_LENGTH, 'UTF-8') : $value;
                }
            } else {
                if (preg_match('#^[\x20-\x7F]*+$#', $value) === 1) {
                    $printable = true;
                    $truncated = strlen($value) > self::MAX_STRING_LENGTH;
                    $value = $truncated ? substr($value, 0, self::MAX_STRING_LENGTH) : $value;
                }
            }

            if ($printable) {
                return json_encode($value, self::JSON_ENCODE_OPTIONS) . ($truncated ? ' (truncated)' : '');
            }
        }

        return get_debug_type($value);
    }

}
