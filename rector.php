<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    // Règles pour PHP 8.2
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ]);

    // Activer le typage strict
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // Configuration spécifique
    $rectorConfig->rule(TypedPropertyFromStrictConstructorRector::class);

    // Ignorer certains dossiers
    $rectorConfig->skip([
        __DIR__ . '/src/Migrations',
    ]);
}; 