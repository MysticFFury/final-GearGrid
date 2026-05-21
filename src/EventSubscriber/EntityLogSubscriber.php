<?php

namespace App\EventSubscriber;

use App\Service\LogService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class EntityLogSubscriber implements EventSubscriber
{
    private array $pendingLogs = [];

    public function __construct(
        private LogService $logService,
        private Security $security
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
            Events::postFlush,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->logEntity('ADD', $args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logEntity('UPDATE', $args->getObject());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->logEntity('DELETE', $args->getObject());
    }

    private function logEntity(string $action, object $entity): void
    {
        if ($entity instanceof \App\Entity\Log) return;
        if ($entity instanceof \App\Entity\User) return; // User actions are logged separately in UserController

        $user = $this->security->getUser();
        if (!$user) return; // Skip logging if no user is authenticated

        // Only log ADD, UPDATE and DELETE actions for staff users (not admin)
        $userRoles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $userRoles);
        
        // Check if user has ROLE_STAFF (and is not admin)
        // Staff users have ROLE_STAFF in their roles array
        $hasStaffRole = in_array('ROLE_STAFF', $userRoles);
        $isStaff = $hasStaffRole && !$isAdmin;
        
        // Only log for staff users (users with ROLE_STAFF but not ROLE_ADMIN)
        if (!$isStaff) {
            return; // Skip logging for admin or regular users
        }

        // Store entity reference for later use (after flush when ID is available)
        $this->pendingLogs[] = [
            'action' => $action,
            'entity' => $entity,
        ];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        // Process logs after flush when entity IDs are available
        if (empty($this->pendingLogs)) {

            return;
        }

        
        foreach ($this->pendingLogs as $logData) {
            $action = $logData['action'];
            $entity = $logData['entity'];
            
            $entityName = $this->getEntityName($entity);
            // After flush, entity should have ID (for ADD) or still have ID (for UPDATE/DELETE)
            $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
            
            // Create descriptive message
            $message = $entityId
                ? sprintf('Staff %s %s #%s', $action, $entityName, $entityId)
                : sprintf('Staff %s %s', $action, $entityName);

            $this->logService->addLog(
                $action,
                $message,
                $entity
            );
        }

        // Flush the logs
        $this->logService->flushLogs();
        $this->pendingLogs = [];
    }

    private function getEntityName(object $entity): string
    {
        $class = $entity instanceof \Doctrine\Persistence\Proxy
            ? get_parent_class($entity)
            : get_class($entity);

        return (new \ReflectionClass($class))->getShortName();
    }
}
