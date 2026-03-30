<?php

namespace App\Service;

use App\Entity\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class LogService
{
    private array $queuedLogs = [];

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
    ) {}

    public function addLog(string $action, string $message, ?object $entityObject = null): void
    {
        $log = new Log();
        // Ensure action is uppercase (LOGIN, LOGOUT, UPDATE, DELETE)
        $log->setAction(strtoupper($action))
            ->setMessage($message)
            ->setStatus('active')
            ->setCreatedAt(new \DateTime());

        // User info
        $user = $this->security->getUser();
        if ($user) {
            $log->setUserName($user->getUserIdentifier());
            $userRoles = $user->getRoles();
            $log->setUserRole(implode(', ', $userRoles));
        }

        // Entity name handling:
        // - For LOGIN/LOGOUT: entity should be null/empty
        // - For ADD/UPDATE/DELETE: entity should show the entity name (Product, Order, Category, etc.)
        $actionUpper = strtoupper($action);
        if (in_array($actionUpper, ['ADD', 'UPDATE', 'DELETE']) && $entityObject) {
            $class = ($entityObject instanceof \Doctrine\Persistence\Proxy)
                ? get_parent_class($entityObject)
                : get_class($entityObject);

            $log->setEntity((new \ReflectionClass($class))->getShortName());
        } else {
            // For LOGIN/LOGOUT or other actions without entity, set entity to null
            $log->setEntity(null);
        }

        $this->queuedLogs[] = $log;
    }

    public function flushLogs(): void
    {
        foreach ($this->queuedLogs as $log) {
            $this->em->persist($log);
        }

        if (!empty($this->queuedLogs)) {
            $this->em->flush();
        }

        $this->queuedLogs = [];
    }
}
