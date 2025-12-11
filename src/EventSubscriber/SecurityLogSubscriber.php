<?php

namespace App\EventSubscriber;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityLogSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;

    public function __construct(Security $security, EntityManagerInterface $em)
    {
        $this->security = $security;
        $this->em = $em;
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

        $log = new Log();
        $log->setAction('LOGIN')
            ->setMessage("User '{$user->getUserIdentifier()}' logged in")
            ->setStatus('active')
            ->setUserName($user->getUserIdentifier())
            ->setUserRole(implode(', ', $user->getRoles()))
            ->setEntity(null)
            ->setCreatedAt(new \DateTime()); // matches Log entity

        $this->em->persist($log);
        $this->em->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token) return;

        $user = $token->getUser();
        if (!is_object($user)) return;

        $log = new Log();
        $log->setAction('LOGOUT')
            ->setMessage("User '{$user->getUserIdentifier()}' logged out")
            ->setStatus('active')
            ->setUserName($user->getUserIdentifier())
            ->setUserRole(implode(', ', $user->getRoles()))
            ->setEntity(null)
            ->setCreatedAt(new \DateTime()); // matches Log entity

        $this->em->persist($log);
        $this->em->flush();
    }
}
