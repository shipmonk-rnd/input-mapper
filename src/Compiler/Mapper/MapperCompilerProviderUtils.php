<?php declare(strict_types = 1);

namespace ShipMonk\InputMapper\Compiler\Mapper;

use function array_pop;

class MapperCompilerProviderUtils
{

    /**
     * Yields the given provider and all providers transitively nested in composite providers.
     *
     * @return iterable<InputMapperCompilerProvider|OutputMapperCompilerProvider>
     */
    public static function iterate(InputMapperCompilerProvider|OutputMapperCompilerProvider $provider): iterable
    {
        $stack = [$provider];

        while ($stack !== []) {
            $current = array_pop($stack);
            yield $current;

            if ($current instanceof CompositeMapperCompilerProvider) {
                foreach ($current->getInnerMapperCompilerProviders() as $innerProvider) {
                    $stack[] = $innerProvider;
                }
            }
        }
    }

}
