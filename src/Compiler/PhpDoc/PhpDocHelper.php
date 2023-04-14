<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\PhpDoc;

use function array_filter;
use function count;
use function implode;

class PhpDocHelper
{

    /**
     * @param list<?string> $lines
     */
    public static function fromLines(array $lines): string
    {
        $lines = array_filter($lines, static fn (?string $line): bool => $line !== null);

        if (count($lines) === 0) {
            return '';
        }

        return "/**\n * " . implode("\n * ", $lines) . "\n */";
    }

}
