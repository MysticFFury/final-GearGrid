<?php

namespace App\EventSubscriber;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityLogSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(Security $security, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator)
    {
        $this->security = $security;
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!is_object($user)) return;

        // Determine user type for better logging
        $userRoles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $userRoles);
        $isStaff = in_array('ROLE_STAFF', $userRoles) && !$isAdmin;
        $userType = $isAdmin ? 'Admin' : ($isStaff ? 'Staff' : 'User');

        $log = new Log();
        $log->setAction('LOGIN')
            ->setMessage("{$userType} '{$user->getUserIdentifier()}' logged in")
            ->setStatus('active')
            ->setUserName($user->getUserIdentifier())
            ->setUserRole(implode(', ', $userRoles))
            ->setEntity(null)
            ->setCreatedAt(new \DateTime());

        $this->em->persist($log);
        $this->em->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token) return;

        $user = $token->getUser();
        if (!is_object($user)) return;

        // Determine user type for better logging
        $userRoles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $userRoles);
        $isStaff = in_array('ROLE_STAFF', $userRoles) && !$isAdmin;
        $userType = $isAdmin ? 'Admin' : ($isStaff ? 'Staff' : 'User');

        $log = new Log();
        $log->setAction('LOGOUT')
            ->setMessage("{$userType} '{$user->getUserIdentifier()}' logged out")
            ->setStatus('active')
            ->setUserName($user->getUserIdentifier())
            ->setUserRole(implode(', ', $userRoles))
            ->setEntity(null)
            ->setCreatedAt(new \DateTime());

        $this->em->persist($log);
        $this->em->flush();

        // Redirect to login page after logout
        $response = new RedirectResponse($this->urlGenerator->generate('app_login'));
        
        // Prevent back button access by setting cache control headers
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('max-age', '0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        $event->setResponse($response);
    }
}
