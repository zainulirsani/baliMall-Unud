<?php

namespace App\Entity;

use App\Repository\OrderChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=OrderChangeLogRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class OrderChangeLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $orderId;

    /**
     * @ORM\Column(type="integer")
     */
    private $userId;

    /**
     * @ORM\Column(type="json")
     */
    private $previousValues = [];

    /**
     * @ORM\Column(type="json")
     */
    private $currentValues = [];

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $changes = [];

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="json")
     */
    private $user = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getPreviousValues(): ?array
    {
        return $this->previousValues;
    }

    public function setPreviousValues(array $previousValues): self
    {
        $this->previousValues = $previousValues;

        return $this;
    }

    public function getCurrentValues(): ?array
    {
        return $this->currentValues;
    }

    public function setCurrentValues(array $currentValues): self
    {
        $this->currentValues = $currentValues;

        return $this;
    }

    public function getChanges(): ?array
    {
        return $this->changes;
    }

    public function setChanges(?array $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }


    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTime();

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function setUser(array $user): self
    {
        $this->user = $user;

        return $this;
    }
}
