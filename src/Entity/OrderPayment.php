<?php

namespace App\Entity;

use App\Utility\GoogleMailHandler;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="order_payment")
 * @ORM\Entity(repositoryClass="App\Repository\OrderPaymentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class OrderPayment extends BaseEntity
{
    public const BANK_TRANSFER = 'bank_transfer';
    public const VIRTUAL_ACCOUNT = 'virtual_account';

    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * One Order Payment <==> One Order.
     * @ORM\OneToOne(targetEntity="App\Entity\Order", inversedBy="payment", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\Length(max=100)
     */
    private $invoice;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max=100)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank()
     * @Assert\Length(max=100)
     * @Assert\Choice({"bank_transfer", "virtual_account", "qris"}))
     */
    private $type;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank()
     */
    private $date;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $attachment;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     * @Assert\GreaterThan(0)
     */
    private $nominal;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $bankName;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Length(max=200)
     */
    private $bankAccountName;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Length(max=200)
     */
    private $bankAccountNumber;

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
        $this->type = self::BANK_TRANSFER;
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

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice(string $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = GoogleMailHandler::validate($email);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): void
    {
        $this->date = $date;
    }

    public function getAttachment()
    {
        return $this->attachment;
    }

    public function setAttachment(string $attachment): void
    {
        $this->attachment = $attachment;
    }

    public function getNominal()
    {
        return $this->nominal;
    }

    public function setNominal(float $nominal): void
    {
        $this->nominal = $nominal;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getBankName()
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getBankAccountName()
    {
        return $this->bankAccountName;
    }

    public function setBankAccountName(?string $bankAccountName): void
    {
        $this->bankAccountName = $bankAccountName;
    }

    public function getBankAccountNumber()
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(?string $bankAccountNumber): void
    {
        $this->bankAccountNumber = $bankAccountNumber;
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

    public function __clone()
    {
        $this->id = null;
    }
}
