<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($request->getPayload()->getString('password')),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Role-aware landing:
        // - Admin/Staff -> /dashboard
        // - Customers (ROLE_USER) -> /customer-landing
        $userRoles = method_exists($token, 'getRoleNames') ? $token->getRoleNames() : [];
        $hasAdminOrStaff = in_array('ROLE_ADMIN', $userRoles, true) || in_array('ROLE_STAFF', $userRoles, true);

        $fallbackTarget = $hasAdminOrStaff
            ? $this->urlGenerator->generate('app_dashboard')
            : $this->urlGenerator->generate('app_customer_landing');

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            // If a customer tries to end up anywhere else, always send them to customer landing.
            // This prevents redirect loops (e.g. hitting /dashboard which customers can't access).
            if (!$hasAdminOrStaff && is_string($targetPath) && $targetPath !== $this->urlGenerator->generate('app_customer_landing')) {
                return new RedirectResponse($fallbackTarget);
            }

            // Admin/Staff: keep the original target unless it is customer landing.
            if ($hasAdminOrStaff && is_string($targetPath) && str_contains($targetPath, '/customer-landing')) {
                return new RedirectResponse($fallbackTarget);
            }

            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($fallbackTarget);
        // throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
