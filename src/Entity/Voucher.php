<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="voucher", indexes={@ORM\Index(name="voucher_code_idx", columns={"code"})})
 * @ORM\Entity(repositoryClass="App\Repository\VoucherRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Voucher extends BaseEntity
{
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_PERCENTAGE = 'percentage';
    public const BASE_TYPE_COUPON = 'coupon';
    public const BASE_TYPE_VOUCHER = 'voucher';

    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", nullable=true, options={"default": "coupon"})
     */
    private $baseType;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $percentage;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(0)
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\NotBlank()
     */
    private $startAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\NotBlank()
     */
    private $endAt;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    private $usageLimit;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\Type(type="numeric")
     */
    private $usagePerUser;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "draft"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"publish", "draft", "deleted"}))
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $qrImage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $validFor;

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
        $this->type = self::TYPE_FIXED_AMOUNT;
        $this->baseType = self::BASE_TYPE_COUPON;
        $this->percentage = 0;
        $this->usageLimit = 1;
        $this->usagePerUser = 1;
        $this->status = 'draft';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getBaseType(): string
    {
        return $this->baseType;
    }

    public function setBaseType(string $baseType): void
    {
        $this->baseType = $baseType;
    }

    public function getPercentage(): int
    {
        return $this->percentage;
    }

    public function setPercentage(int $percentage): void
    {
        $this->percentage = $percentage;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getStartAt()
    {
        return $this->startAt;
    }

    public function setStartAt(?DateTime $startAt): void
    {
        $this->startAt = $startAt;
    }

    public function getEndAt()
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTime $endAt): void
    {
        $this->endAt = $endAt;
    }

    public function getUsageLimit(): int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(int $usageLimit): void
    {
        $this->usageLimit = $usageLimit;
    }

    public function getUsagePerUser(): int
    {
        return $this->usagePerUser;
    }

    public function setUsagePerUser(int $usagePerUser): void
    {
        $this->usagePerUser = $usagePerUser;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getQrImage()
    {
        return $this->qrImage;
    }

    public function setQrImage(?string $qrImage): void
    {
        $this->qrImage = $qrImage;
    }

    public function getValidFor()
    {
        return array_unique(json_decode($this->validFor, true));
    }

    public function setValidFor(array $validFor = ['ROLE_USER', 'ROLE_USER_BUYER']): void
    {
        $this->validFor = json_encode($validFor);
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
