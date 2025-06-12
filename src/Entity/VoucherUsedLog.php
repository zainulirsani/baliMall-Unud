<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="voucher_used_log", indexes={
 *     @ORM\Index(name="voucher_used_log_voucher_id_idx", columns={"voucher_id"}),
 *     @ORM\Index(name="voucher_used_log_user_id_idx", columns={"user_id"}),
 *     @ORM\Index(name="voucher_used_log_order_id_idx", columns={"order_id"}),
 *     @ORM\Index(name="voucher_used_log_order_shared_id_idx", columns={"order_shared_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\VoucherUsedLogRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class VoucherUsedLog extends BaseEntity
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
    private $voucherId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $userId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $orderSharedId;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $voucherAmount;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $orderAmount;

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
        $this->voucherId = 0;
        $this->userId = 0;
        $this->orderId = 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getVoucherId(): int
    {
        return $this->voucherId;
    }

    public function setVoucherId(int $voucherId): void
    {
        $this->voucherId = $voucherId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderSharedId()
    {
        return $this->orderSharedId;
    }

    public function setOrderSharedId(?string $orderSharedId): void
    {
        $this->orderSharedId = $orderSharedId;
    }

    public function getVoucherAmount()
    {
        return $this->voucherAmount;
    }

    public function setVoucherAmount(float $voucherAmount): void
    {
        $this->voucherAmount = $voucherAmount;
    }

    public function getOrderAmount()
    {
        return $this->orderAmount;
    }

    public function setOrderAmount(float $orderAmount): void
    {
        $this->orderAmount = $orderAmount;
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
