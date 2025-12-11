<?php

namespace App\EventListener;

use App\Entity\Log;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class EntityCrudLogger
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->logAction($args, 'Create');
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->logAction($args, 'Update');
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->logAction($args, 'Delete');
    }

    private function logAction(LifecycleEventArgs $args, string $action): void
    {
        $entity = $args->getObject();
        if ($entity instanceof Log) {
            return;
        }

        $em = $args->getObjectManager();
        $user = $this->security->getUser();

        $log = new Log();
        $log->setAction($action);
        $log->setMessage(sprintf('%s entity %s', $action, get_class($entity)));
        $realClass = $entity instanceof \Doctrine\Persistence\Proxy ? get_parent_class($entity) : get_class($entity);
        $log->setEntity((new \ReflectionClass($realClass))->getShortName());
        $log->setUserName($user ? $user->getUserIdentifier() : 'anonymous');
        $log->setUserRole($user ? implode(',', $user->getRoles()) : null);

        $em->persist($log);
    }
}
