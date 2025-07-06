<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once __DIR__ . '/../../.Build/vendor/autoload.php';
$classLoader = require __DIR__ . '/../../.Build/vendor/autoload.php';
\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run();
\TYPO3\CMS\Core\Core\Bootstrap::init($classLoader)->get(\TYPO3\CMS\Core\Http\Application::class)->run();

$passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
$hashInstance = $passwordHashFactory->getDefaultHashInstance('BE');

echo 'Hash password generated for fixtures : ' . $hashInstance->getHashedPassword('valid_password') . PHP_EOL;
