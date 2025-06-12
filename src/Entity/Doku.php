<?php

namespace App\Entity;

use App\Repository\DokuRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=DokuRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class Doku
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $invoice_number;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $token_id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expired_date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $service;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acquirer;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $channel;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $requestId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sharedInvoice;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoice_number;
    }

    public function setInvoiceNumber(string $invoice_number): self
    {
        $this->invoice_number = $invoice_number;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTokenId(): ?string
    {
        return $this->token_id;
    }

    public function setTokenId(string $token_id): self
    {
        $this->token_id = $token_id;

        return $this;
    }

    public function getExpiredDate(): ?\DateTimeInterface
    {
        return $this->expired_date;
    }

    public function setExpiredDate(\DateTimeInterface $expired_date): self
    {
        $this->expired_date = $expired_date;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function setService(?string $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function getAcquirer(): ?string
    {
        return $this->acquirer;
    }

    public function setAcquirer(?string $acquirer): self
    {
        $this->acquirer = $acquirer;

        return $this;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function setChannel(?string $channel): self
    {
        $this->channel = $channel;

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

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist()
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTime('now');

        return $this;
    }

    public function getUpdatedAt()
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

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function getSharedInvoice(): ?string
    {
        return $this->sharedInvoice;
    }

    public function setSharedInvoice(?string $sharedInvoice): self
    {
        $this->sharedInvoice = $sharedInvoice;

        return $this;
    }
}
