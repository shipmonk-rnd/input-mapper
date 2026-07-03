<?php declare(strict_types = 1);

namespace ShipMonk\InputMapperDev\PHPStan;

use ReflectionMethod;
use ShipMonk\InputMapper\Runtime\Codec;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;
use function in_array;

class IgnoreDeadInterfaceUsageProvider extends ReflectionBasedMemberUsageProvider
{

    public function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        if ($method->getDeclaringClass()->isInterface() || $method->isAbstract()) {
            return VirtualUsageData::withNote('interface methods kept for unification');
        }

        if (
            in_array($method->getName(), ['decode', 'encode'], true)
            && $method->getDeclaringClass()->implementsInterface(Codec::class)
        ) {
            return VirtualUsageData::withNote('codec methods are invoked by generated mappers');
        }

        return null;
    }

}
