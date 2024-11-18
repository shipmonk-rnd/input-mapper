<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config->ignoreErrorsOnExtensionsAndPaths(
    ['ext-mbstring'],
    [__DIR__ . '/src/Runtime/Exception/MappingFailedException.php'], // optional usages guarded with extension_loaded
    [ErrorType::SHADOW_DEPENDENCY]
);
