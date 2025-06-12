<?php

namespace App\Entity;

use App\Helper\StaticHelper;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_product")
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 */
class OrderProduct extends BaseEntity
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * Many Order Products <==> One Order.
     * @ORM\ManyToOne(targetEntity="App\Entity\Order", inversedBy="orderProducts", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * Many Orders <==> One Product.
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="orders", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $product;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $quantity;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $quantityApproval;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $quantityToSend;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $quantityReceived;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $basePrice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $priceBeforeNegotiation;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $priceShippingNegotiation;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $totalPrice;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="boolean")
     */
    private $withTax;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $taxValue;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $taxNominal;

    /**
     * @ORM\Column(type="bigint", nullable=true, options={"default": 0})
     */
    private $originalId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $originalName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fee;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $fee_nominal;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $deliveryDetails;

    public function __construct()
    {
        $this->id = StaticHelper::generateStr();
        $this->withTax = false;
        $this->originalId = 0;
        $this->priceBeforeNegotiation = 0.00;
        $this->priceShippingNegotiation = 0.00;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getBasePrice()
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    public function getPriceBeforeNegotiation(): float
    {
        return $this->priceBeforeNegotiation;
    }

    public function setPriceBeforeNegotiation(float $priceBeforeNegotiation): void
    {
        $this->priceBeforeNegotiation = $priceBeforeNegotiation;
    }

    public function getPriceShippingNegotiation(): float
    {
        return $this->priceShippingNegotiation;
    }

    public function setPriceShippingNegotiation(float $priceShippingNegotiation): void
    {
        $this->priceShippingNegotiation = $priceShippingNegotiation;
    }

    public function getTotalPrice()
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): void
    {
        $this->totalPrice = $totalPrice;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getWithTax(): bool
    {
        return $this->withTax;
    }

    public function setWithTax(bool $withTax): void
    {
        $this->withTax = $withTax;
    }

    public function getTaxValue(): ?float
    {
        return $this->taxValue;
    }

    public function setTaxValue(?float $taxValue): void
    {
        $this->taxValue = $taxValue;
    }

    public function getTaxNominal(): float
    {
        return $this->taxNominal;
    }

    public function setTaxNominal(float $taxNominal): void
    {
        $this->taxNominal = $taxNominal;
    }

    public function getOriginalId(): int
    {
        return $this->originalId;
    }

    public function setOriginalId(int $originalId = 0): void
    {
        $this->originalId = $originalId;
    }

    public function getOriginalName()
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(?float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFeeNominal(): ?float
    {
        return $this->fee_nominal;
    }

    public function setFeeNominal(?float $fee_nominal): self
    {
        $this->fee_nominal = $fee_nominal;

        return $this;
    }

    public function getDeliveryDetails(): ?array
    {
        return !empty($this->deliveryDetails) ? json_decode($this->deliveryDetails, true) : [];
    }

    public function setDeliveryDetails(array $deliveryDetails = []): self
    {
        $this->deliveryDetails = json_encode($deliveryDetails);

        return $this;
    }

    public function getQuantityApproval(): ?int
    {
        return $this->quantityApproval;
    }

    public function setQuantityApproval(?int $quantityApproval): void
    {
        $this->quantityApproval = $quantityApproval;
    }

    public function getQuantityToSend(): ?int
    {
        return $this->quantityToSend;
    }


    public function setQuantityToSend(?int $quantityToSend): void
    {
        $this->quantityToSend = $quantityToSend;
    }


    public function getQuantityReceived(): ?int
    {
        return $this->quantityReceived;
    }

    public function setQuantityReceived(?int $quantityReceived): void
    {
        $this->quantityReceived = $quantityReceived;
    }
}