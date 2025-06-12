<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_complaint")
 * @ORM\Entity(repositoryClass="App\Repository\OrderComplaintRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class OrderComplaint extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * One Order Complaint <==> One Order.
     * @ORM\OneToOne(targetEntity="App\Entity\Order", inversedBy="complaint", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isResolved;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $resolvedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function __construct()
    {
        $this->isResolved = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getIsResolved(): bool
    {
        return $this->isResolved;
    }

    public function setIsResolved(bool $isResolved): void
    {
        $this->isResolved = $isResolved;
    }

    public function getResolvedAt()
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(): void
    {
        $this->resolvedAt = new DateTime('now');
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
