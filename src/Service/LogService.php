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

    /**
     * Queued rows for EntityLogSubscriber::postFlush (must not call flush() while flushing).
     *
     * @var list<array{action: string, message: string, entity: string}>
     */
    private array $deferredStaffLogs = [];

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Queue a log line to be persisted once (used by EntityLogSubscriber after entity flush).
     */
    public function addLog(string $action, string $message, object $entity): void
    {
        $entityClass = $entity instanceof \Doctrine\Persistence\Proxy
            ? (string) get_parent_class($entity)
            : $entity::class;
        $shortName = (new \ReflectionClass($entityClass))->getShortName();

        $this->deferredStaffLogs[] = [
            'action' => $action,
            'message' => $message,
            'entity' => $shortName,
        ];
    }

    /**
     * Persist queued staff entity logs in a second flush (post-main-flush).
     */
    public function flushLogs(): void
    {
        if ($this->deferredStaffLogs === []) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            $this->deferredStaffLogs = [];

            return;
        }

        foreach ($this->deferredStaffLogs as $row) {
            $log = new Log();
            $log->setAction($row['action'])
                ->setEntity($row['entity'])
                ->setMessage($row['message'])
                ->setUserName($user->getUserIdentifier())
                ->setUserRole(implode(', ', $user->getRoles()))
                ->setStatus('active')
                ->setCreatedAt(new \DateTime());
            $this->em->persist($log);
        }

        $this->deferredStaffLogs = [];
        $this->em->flush();
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