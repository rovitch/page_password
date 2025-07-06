<?php

declare(strict_types=1);

namespace Rovitch\PagePassword\Event;

use Psr\Http\Message\ServerRequestInterface;
use Rovitch\PagePassword\Service\AuthService;

final class BeforeAccessIsGrantedEvent
{
    public function __construct(
        private bool $accessGranted,
        private readonly ServerRequestInterface $request,
        private readonly AuthService $authService,
    ) {}

    public function isAccessGranted(): bool
    {
        return $this->accessGranted;
    }

    public function setAccessGranted(bool $accessGranted): void
    {
        $this->accessGranted = $accessGranted;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getAuthService(): AuthService
    {
        return $this->authService;
    }
}
