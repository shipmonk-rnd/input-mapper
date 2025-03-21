<?php declare(strict_types = 1);

namespace ShipMonkDev\PHPStan;

use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

class IgnoreDeadInterfaceUsageProvider extends ReflectionBasedMemberUsageProvider
{

    public function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        if ($method->getDeclaringClass()->isInterface() || $method->isAbstract()) {
            return VirtualUsageData::withNote('interface methods kept for unification');
        }

        return null;
    }

}
