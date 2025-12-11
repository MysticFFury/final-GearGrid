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
        $log->setAction(strtoupper($action))
            ->setMessage($message)
            ->setCreatedAt(new \DateTime());

        // User info
        $user = $this->security->getUser();
        if ($user) {
            $log->setUserName($user->getUserIdentifier());
            $log->setUserRole($user->getRoles()[0] ?? 'Unknown');
        }

        // Entity name
        if ($entityObject) {
            $class = ($entityObject instanceof \Doctrine\Persistence\Proxy)
                ? get_parent_class($entityObject)
                : get_class($entityObject);

            $log->setEntity((new \ReflectionClass($class))->getShortName());
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
