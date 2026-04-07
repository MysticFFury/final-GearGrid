<?php
// src/Service/LogService.php

namespace App\Service;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class LogService
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function log(string $action, string $entityName, string $message): void
    {
        $user = $this->security->getUser();
        
        // If no one is logged in, don't log anything
        if (!$user) {
            return;
        }

        $log = new Log();
        $log->setAction($action)
            ->setEntity($entityName)
            ->setMessage($message)
            ->setUserName($user->getUserIdentifier())
            ->setUserRole(implode(', ', $user->getRoles()))
            ->setStatus('active')
            ->setCreatedAt(new \DateTime());

        $this->em->persist($log);
        $this->em->flush();
    }
}