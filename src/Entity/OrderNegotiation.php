<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="order_negotiation", indexes={
 *     @ORM\Index(name="order_negotiation_product_id_idx", columns={"product_id"}),
 *     @ORM\Index(name="order_negotiation_submitted_by_idx", columns={"submitted_by"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class OrderNegotiation extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Order Negotiations <==> One Order.
     * @ORM\ManyToOne(targetEntity="App\Entity\Order", inversedBy="orderNegotiations", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $productId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $submittedBy;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice({"buyer", "seller"})
     */
    private $submittedAs;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $negotiatedPrice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $taxNominalPrice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": 0})
     */
    private $negotiatedShippingPrice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": 0})
     */
    private $taxNominalShipping;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": 0})
     */
    private $taxValue;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $executionTime;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $isApproved;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $merchantApproval;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $customerApproval;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $batch;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

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
        $this->productId = 0;
        $this->submittedBy = 0;
        $this->isApproved = false;
        $this->merchantApproval = false;
        $this->customerApproval = false;
        $this->batch = 0;
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

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getSubmittedBy(): int
    {
        return $this->submittedBy;
    }

    public function setSubmittedBy(int $submittedBy): void
    {
        $this->submittedBy = $submittedBy;
    }

    public function getSubmittedAs()
    {
        return $this->submittedAs;
    }

    public function setSubmittedAs(?string $submittedAs): void
    {
        $this->submittedAs = $submittedAs;
    }

    public function getNegotiatedPrice()
    {
        return $this->negotiatedPrice;
    }

    public function setNegotiatedPrice(float $negotiatedPrice): void
    {
        $this->negotiatedPrice = $negotiatedPrice;
    }

    public function setTaxNominalPrice(float $taxNominalPrice): void
    {
        $this->taxNominalPrice = $taxNominalPrice;
    }

    public function getTaxNominalPrice()
    {
        return $this->taxNominalPrice;
    }

    public function getTaxNominalShipping()
    {
        return $this->taxNominalShipping;
    }

    public function setTaxNominalShipping(float $taxNominalShipping): void
    {
        $this->taxNominalShipping = $taxNominalShipping;
    }

    public function getTaxValue()
    {
        return $this->taxValue;
    }

    public function setTaxValue(float $taxValue): void
    {
        $this->taxValue = $taxValue;
    }

    public function getNegotiatedShippingPrice()
    {
        return $this->negotiatedShippingPrice;
    }

    public function setNegotiatedShippingPrice(float $negotiatedShippingPrice): void
    {
        $this->negotiatedShippingPrice = $negotiatedShippingPrice;
    }

    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    public function setExecutionTime(string $executionTime): void
    {
        $this->executionTime = $executionTime;
    }

    public function getIsApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): void
    {
        $this->isApproved = $isApproved;
    }

    public function getMerchantApproval(): bool
    {
        return $this->merchantApproval;
    }

    public function setMerchantApproval(bool $merchantApproval): void
    {
        $this->merchantApproval = $merchantApproval;
    }

    public function getCustomerApproval(): bool
    {
        return $this->customerApproval;
    }

    public function setCustomerApproval(bool $customerApproval): void
    {
        $this->customerApproval = $customerApproval;
    }

    public function getBatch(): int
    {
        return $this->batch;
    }

    public function setBatch(int $batch): void
    {
        $this->batch = $batch;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
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
