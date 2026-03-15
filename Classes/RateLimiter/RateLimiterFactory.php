<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\RateLimiter;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory as SymfonyRateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\RateLimiter\Storage\CachingFrameworkStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RateLimiterFactory
{
    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function createPagePasswordRateLimiter(ServerRequestInterface $request): LimiterInterface
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $configuration = $extensionConfiguration->get('page_password');
        $normalizedParams = $request->getAttribute('normalizedParams') ?? NormalizedParams::createFromRequest($request);
        $remoteIp = $normalizedParams->getRemoteAddress();
        $limiterId = sha1('page-password');
        $limit = (int)($configuration['loginRateLimit'] ?? 5);
        $interval = $configuration['loginRateLimitInterval'] ?? '15 minutes';

        $enabled = $limit > 0;
        $config = [
            'id' => $limiterId,
            'policy' => ($enabled ? 'sliding_window' : 'no_limit'),
            'limit' => $limit,
            'interval' => $interval,
        ];

        $storage = ($enabled ? GeneralUtility::makeInstance(CachingFrameworkStorage::class) : new InMemoryStorage());
        $limiterFactory = new SymfonyRateLimiterFactory(
            $config,
            $storage
        );

        return $limiterFactory->create($remoteIp);
    }
}
