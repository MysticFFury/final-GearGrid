<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {
    }

    public function supports(Request $request): ?bool
    {
        // This authenticator only triggers on the check route
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google_staff');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email = $googleUser->getEmail();

                // 1. Check if user exists in the GearGrid database
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2. If no user exists, create a new one!
                $user = new User();
                $user->setEmail($email);
                
                // Extract name from Google account or use email prefix as fallback
                $name = $googleUser->getName() ?? explode('@', $email)[0];
                $user->setName($name);
                
                // Assign a staff role automatically
                $user->setRoles(['ROLE_STAFF']);
                
                
                // Google users are automatically verified since they're authenticated via Google
                $user->setIsVerified(true);
                $user->setEmail($email);
                $user->setRoles(['ROLE_STAFF']);
                
                // No password needed for OAuth users - it's already null from entity construction
                // Password is handled by OAuth2, not set here

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirect based on user roles
        $user = $token->getUser();
        
        // Staff and Admin go to dashboard
        if (in_array('ROLE_STAFF', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            return new RedirectResponse($this->router->generate('app_dashboard'));
        }
        
        // Other users go to customer landing
        return new RedirectResponse($this->router->generate('app_customer_landing'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Redirect back to login if something goes wrong
        return new RedirectResponse($this->router->generate('app_login'));
    }
}