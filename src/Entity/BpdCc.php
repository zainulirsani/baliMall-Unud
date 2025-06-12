<?php

namespace App\Entity;

use DateTime;
use App\Repository\BpdCcRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BpdCcRepository::class)
 */
class BpdCc
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="bpdCcs")
     * @ORM\JoinColumn(nullable=false)
     */
    private $orders;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $trxId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $externalId;

    /**
     * @ORM\Column(type="decimal", precision=16, scale=2)
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=12)
     */
    private $referenceNo;

    /**
     * @ORM\Column(type="string", length=19)
     */
    private $cpan;

    /**
     * @ORM\Column(type="string", length=8)
     */
    private $ott;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expiredIn;

    /**
     * @ORM\Column(type="text")
     */
    private $requestData;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $response;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\OneToMany(targetEntity=RefundBpd::class, mappedBy="ccBpd")
     */
    private $refundBpds;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $approvalCode;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $bindingType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bindingToken;

    public function __construct()
    {
        $this->refundBpds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?Order
    {
        return $this->orders;
    }

    public function setOrders(?Order $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getTrxId(): ?string
    {
        return $this->trxId;
    }

    public function setTrxId(string $trxId): self
    {
        $this->trxId = $trxId;

        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

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

    public function getReferenceNo(): ?string
    {
        return $this->referenceNo;
    }

    public function setReferenceNo(string $referenceNo): self
    {
        $this->referenceNo = $referenceNo;

        return $this;
    }

    public function getCpan(): ?string
    {
        return $this->cpan;
    }

    public function setCpan(string $cpan): self
    {
        $this->cpan = $cpan;

        return $this;
    }

    public function getOtt(): ?string
    {
        return $this->ott;
    }

    public function setOtt(string $ott): self
    {
        $this->ott = $ott;

        return $this;
    }

    public function getExpiredIn(): ?\DateTimeInterface
    {
        return $this->expiredIn;
    }

    public function setExpiredIn(\DateTimeInterface $expiredIn): self
    {
        $this->expiredIn = $expiredIn;

        return $this;
    }

    public function getRequestData(): ?string
    {
        return $this->requestData;
    }

    public function setRequestData(string $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTime('now');
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTime('now');
    }

    /**
     * @return Collection|RefundBpd[]
     */
    public function getRefundBpds(): Collection
    {
        return $this->refundBpds;
    }

    public function addRefundBpd(RefundBpd $refundBpd): self
    {
        if (!$this->refundBpds->contains($refundBpd)) {
            $this->refundBpds[] = $refundBpd;
            $refundBpd->setCcBpd($this);
        }

        return $this;
    }

    public function removeRefundBpd(RefundBpd $refundBpd): self
    {
        if ($this->refundBpds->removeElement($refundBpd)) {
            // set the owning side to null (unless already changed)
            if ($refundBpd->getCcBpd() === $this) {
                $refundBpd->setCcBpd(null);
            }
        }

        return $this;
    }

    public function getApprovalCode(): ?string
    {
        return $this->approvalCode;
    }

    public function setApprovalCode(?string $approvalCode): self
    {
        $this->approvalCode = $approvalCode;

        return $this;
    }

    public function getBindingType(): ?string
    {
        return $this->bindingType;
    }

    public function setBindingType(?string $bindingType): self
    {
        $this->bindingType = $bindingType;

        return $this;
    }

    public function getBindingToken(): ?string
    {
        return $this->bindingToken;
    }

    public function setBindingToken(?string $bindingToken): self
    {
        $this->bindingToken = $bindingToken;

        return $this;
    }
}
