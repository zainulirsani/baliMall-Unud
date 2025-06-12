<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="store_owner_log", indexes={
 *     @ORM\Index(name="store_owner_log_store_id_idx", columns={"store_id"}),
 *     @ORM\Index(name="store_owner_log_current_owner_idx", columns={"current_owner"}),
 *     @ORM\Index(name="store_owner_log_previous_owner_idx", columns={"previous_owner"}),
 *     @ORM\Index(name="store_owner_log_updated_by_idx", columns={"updated_by"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\StoreOwnerLogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class StoreOwnerLog extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    private $storeId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
     * @Assert\GreaterThan(value="0", message="store.invalid_new_owner")
     */
    private $currentOwner;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    private $previousOwner;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     */
    private $updatedBy;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank()
     */
    private $reason;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function getCurrentOwner()
    {
        return $this->currentOwner;
    }

    public function setCurrentOwner(int $currentOwner): void
    {
        $this->currentOwner = $currentOwner;
    }

    public function getPreviousOwner()
    {
        return $this->previousOwner;
    }

    public function setPreviousOwner(int $previousOwner): void
    {
        $this->previousOwner = $previousOwner;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(int $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTime('now');
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTime('now');
    }
}
