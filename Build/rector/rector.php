<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO313\v0\RemoveSpecialPropertiesOfPageArraysRector;
use Ssch\TYPO3Rector\TYPO313\v2\RemoveAddRootLineFieldsRector;
use Ssch\TYPO3Rector\TYPO313\v4\MigratePluginContentElementAndPluginSubtypesRector;
use Ssch\TYPO3Rector\TYPO314\v0\DropFifthParameterForExtensionUtilityConfigurePluginRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../*.php',
    ])
    ->withPhpSets(php82: true)
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ])
    ->withPHPStanConfigs([Typo3Option::PHPSTAN_FOR_RECTOR_PATH])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    // To have a better analysis from PHPStan, we teach it here some more things
    ->withPHPStanConfigs([
        Typo3Option::PHPSTAN_FOR_RECTOR_PATH,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withConfiguredRule(ExtEmConfRector::class, [
        ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.2.0-8.5.99',
        ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '12.4.0-14.99.99',
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ])
    // If you use importNames(), you should consider excluding some TYPO3 files.
    ->withSkip([
        RemoveParentCallWithoutParentRector::class,
        // Plugins are only used in typoScript PAGE.10 = USER, no need to migrate to CType
        MigratePluginContentElementAndPluginSubtypesRector::class,
        // Condition is set manually for typo < 13
        RemoveSpecialPropertiesOfPageArraysRector::class,
        // Condition is set manually for typo < 13
        RemoveAddRootLineFieldsRector::class,
        // Condition is set manually for typo < 14
        DropFifthParameterForExtensionUtilityConfigurePluginRector::class,
    ])
;
