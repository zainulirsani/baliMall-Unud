<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="notification", indexes={
 *     @ORM\Index(name="notification_buyer_id_idx", columns={"buyer_id"}),
 *     @ORM\Index(name="notification_seller_id_idx", columns={"seller_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\NotificationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Notification extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $buyerId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $sellerId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSentToBuyer;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSentToSeller;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $isAdmin;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $url;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $readAt;

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
        $this->isSentToBuyer = false;
        $this->isSentToSeller = false;
        $this->isAdmin = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBuyerId()
    {
        return $this->buyerId;
    }

    public function setBuyerId(int $buyerId): void
    {
        $this->buyerId = $buyerId;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function setSellerId(int $sellerId): void
    {
        $this->sellerId = $sellerId;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getIsSentToBuyer(): bool
    {
        return $this->isSentToBuyer;
    }

    public function setIsSentToBuyer(bool $isSentToBuyer): void
    {
        $this->isSentToBuyer = $isSentToBuyer;
    }

    public function getIsSentToSeller(): bool
    {
        return $this->isSentToSeller;
    }

    public function setIsSentToSeller(bool $isSentToSeller): void
    {
        $this->isSentToSeller = $isSentToSeller;
    }

    public function getIsAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getReadAt()
    {
        return $this->readAt;
    }

    public function setReadAt(): void
    {
        $this->readAt = new DateTime('now');
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
