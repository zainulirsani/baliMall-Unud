<?php

namespace App\Entity;

use App\Repository\MidtransRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=MidtransRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Midtrans
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $sharedInvoice;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

//    /**
//     * @ORM\Column(type="string", length=255)
//     */
//    private $token;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $transactionTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $transactionId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $paymentType;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $response = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $orderId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSharedInvoice(): ?string
    {
        return $this->sharedInvoice;
    }

    public function setSharedInvoice(string $sharedInvoice): self
    {
        $this->sharedInvoice = $sharedInvoice;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getTransactionTime(): ?string
    {
        return $this->transactionTime;
    }

    public function setTransactionTime(string $transactionTime): self
    {
        $this->transactionTime = $transactionTime;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    public function getPaymentType(): ?string
    {
        return $this->paymentType;
    }

    public function setPaymentType(?string $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function setResponse(?array $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTime('now');

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
        $this->updatedAt = new DateTime('now');

        return $this;
    }


    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }
}
