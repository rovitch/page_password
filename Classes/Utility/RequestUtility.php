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
}
