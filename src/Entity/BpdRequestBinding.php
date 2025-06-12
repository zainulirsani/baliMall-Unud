<?php
// src/Entity/BpdRequestBinding.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="bpd_request_bindings")
 */
class BpdRequestBinding extends BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $linkId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $issuerToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $ott;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     */
    private $customerPan;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $merchantPan;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $merchantName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $issuerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $acquirerName;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $partnerReferenceNo;

    /**
     * user_id == custIdMerchant
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $is_expired;


    public function __construct()
    {
        $this->created = new \DateTime();
    }

    // Getter and Setter methods

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLinkId(): ?string
    {
        return $this->linkId;
    }

    public function setLinkId(string $linkId): self
    {
        $this->linkId = $linkId;
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

    public function getIssuerToken(): ?string
    {
        return $this->issuerToken;
    }

    public function setIssuerToken(string $issuerToken): self
    {
        $this->issuerToken = $issuerToken;
        return $this;
    }

    public function getPartnerReferenceNo(): ?string
    {
        return $this->partnerReferenceNo;
    }

    public function setPartnerReferenceNo(string $partnerReferenceNo): self
    {
        $this->partnerReferenceNo = $partnerReferenceNo;
        return $this;
    }

    public function getOtt(): ?string
    {
        return $this->ott;
    }

    public function setOtt(?string $ott): self
    {
        $this->ott = $ott;
        return $this;
    }

    public function getCustomerPan(): ?string
    {
        return $this->customerPan;
    }

    public function setCustomerPan(string $customerPan): self
    {
        $this->customerPan = $customerPan;
        return $this;
    }

    public function getMerchantPan(): ?string
    {
        return $this->merchantPan;
    }

    public function setMerchantPan(?string $merchantPan): self
    {
        $this->merchantPan = $merchantPan;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function getAcquirerName(): ?string
    {
        return $this->acquirerName;
    }

    public function setAcquirerName(string $acquirerName): self
    {
        $this->acquirerName = $acquirerName;
        return $this;
    }

    public function getIssuerName(): ?string
    {
        return $this->issuerName;
    }

    public function setIssuerName(string $issuerName): self
    {
        $this->issuerName = $issuerName;
        return $this;
    }

    public function getNotes(): ?String
    {
        return $this->notes;
    }

    public function setNotes(string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getIsExpired(): ?String
    {
        return $this->is_expired;
    }

    public function setIsExpired($is_expired): self
    {
        $this->is_expired = $is_expired;

        return $this;
    }
}
