<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Utility;

use Psr\Http\Message\ServerRequestInterface;

class RequestUtility
{
    /**
     * @param ServerRequestInterface $request
     * @return array<int|string>
     */
    public static function extractParameters(ServerRequestInterface $request): array
    {
        return $request->getParsedBody()['tx_pagepassword_form']
            ?? $request->getQueryParams()['tx_pagepassword_form']
            ?? [];
    }

    public static function extractProtectedPageId(ServerRequestInterface $request): int
    {
        $parameters = self::extractParameters($request);
        return isset($parameters['uid']) && (int)$parameters['uid'] > 0 ? (int)$parameters['uid'] : 0;
    }

    /**
     * Language of the protected page the login form was requested for. The login page may live on another
     * site and is rendered in that site's default language, so the protected page's language is passed along.
     */
    public static function extractProtectedPageLanguageId(ServerRequestInterface $request): ?int
    {
        $parameters = self::extractParameters($request);
        return isset($parameters['language']) && is_numeric($parameters['language']) && (int)$parameters['language'] >= 0
            ? (int)$parameters['language']
            : null;
    }
}
