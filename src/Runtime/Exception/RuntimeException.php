<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Runtime\Exception;

use RuntimeException as NativeRuntimeException;
use Throwable;

abstract class RuntimeException extends NativeRuntimeException
{

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

}
