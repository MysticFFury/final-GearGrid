<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRepository::class)]
class Log
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $userName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $userRole = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $entity = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'active';
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getAction(): ?string { return $this->action; }
    public function setAction(string $action): self { $this->action = $action; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): self { $this->message = $message; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getUserName(): ?string { return $this->userName; }
    public function setUserName(?string $userName): self { $this->userName = $userName; return $this; }
    public function getUserRole(): ?string { return $this->userRole; }
    public function setUserRole(?string $userRole): self { $this->userRole = $userRole; return $this; }
    public function getEntity(): ?string { return $this->entity; }
    public function setEntity(?string $entity): self { $this->entity = $entity; return $this; }
}
