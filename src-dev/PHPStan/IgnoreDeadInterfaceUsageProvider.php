<?php declare(strict_types = 1);

namespace ShipMonkDev\PHPStan;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;

class IgnoreDeadInterfaceUsageProvider extends ReflectionBasedMemberUsageProvider
{

    public function shouldMarkMethodAsUsed(ReflectionMethod $method): bool
    {
        return $method->getDeclaringClass()->isInterface() || $method->isAbstract();
    }

}
