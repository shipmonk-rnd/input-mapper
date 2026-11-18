<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

/**
 * Implemented by mapper compiler providers that are composed of other providers.
 *
 * Allows typed traversal of a provider graph without reflecting over provider internals,
 * e.g. to discover all delegated classes (@see MapperCompilerProviderUtils::iterate()).
 *
 * Every provider holding other providers should implement this interface and expose them,
 * no matter how deep in its properties they are stored.
 */
interface CompositeMapperCompilerProvider
{

    /**
     * @return list<InputMapperCompilerProvider|OutputMapperCompilerProvider>
     */
    public function getInnerMapperCompilerProviders(): array;

}
