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
    private bool $hasLogs = false;

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
        $this->logEntity('create', $args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logEntity('update', $args->getObject());
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->logEntity('delete', $args->getObject());
    }

    private function logEntity(string $action, object $entity): void
    {
        if ($entity instanceof \App\Entity\Log) return;
        if ($entity instanceof \App\Entity\User) return; // User actions are logged separately in UserController

        $user = $this->security->getUser();
        if (!$user) return; // Skip logging if no user is authenticated

        $entityName = $this->getEntityName($entity);
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : null;
        $actionLabel = strtoupper($action);
        
        // Check if user is admin or staff
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        $userType = $isAdmin ? 'Admin' : 'Staff';
        
        // Create more descriptive message
        $message = $entityId
            ? sprintf('%s %s %s #%s', $userType, $actionLabel, $entityName, $entityId)
            : sprintf('%s %s %s', $userType, $actionLabel, $entityName);

        $this->logService->addLog(
            $actionLabel,
            $message,
            $entity
        );

        $this->hasLogs = true;
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->hasLogs) {
            $this->logService->flushLogs();
            $this->hasLogs = false;
        }
    }

    private function getEntityName(object $entity): string
    {
        $class = $entity instanceof \Doctrine\Persistence\Proxy
            ? get_parent_class($entity)
            : get_class($entity);

        return (new \ReflectionClass($class))->getShortName();
    }
}
