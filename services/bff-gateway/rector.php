<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/test',
    ])
    ->withSkip([
        __DIR__ . '/test/bootstrap.php',
    ])
    ->withCache(__DIR__ . '/runtime/rector')
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withImportNames(removeUnusedImports: true);
