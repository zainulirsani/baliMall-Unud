<?php

namespace App\Entity;

use App\Repository\DisbursementRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=DisbursementRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Disbursement
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", unique=true)
     */
    private $orderId;

    /**
     * @ORM\Column(type="float")
     */
    private $productFee;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $ppn;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $pph;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $bankFee;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $managementFee;

    /**
     * @ORM\Column(type="float")
     * @Assert\NotBlank()
     */
    private $otherFee;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Choice({"pending","processed","done"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="float")
     */
    private $totalProductPrice;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Assert\NotBlank()
     */
    private $total;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $logs = [];

    /**
     * @ORM\Column(type="float")
     */
    private $persentase_ppn;

    /**
     * @ORM\Column(type="float")
     */
    private $persentase_pph;

    /**
     * @ORM\Column(type="float")
     */
    private $persentase_bank;

    /**
     * @ORM\Column(type="float")
     */
    private $persentase_management;

    /**
     * @ORM\Column(type="float")
     */
    private $persentase_other;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $payment_proof;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rekening_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bank_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nomor_rekening;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $order_shipping_price;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $statusChangeTime;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getProductFee(): ?float
    {
        return $this->productFee;
    }

    public function setProductFee(float $productFee): self
    {
        $this->productFee = $productFee;

        return $this;
    }

    public function getPpn(): ?float
    {
        return $this->ppn;
    }

    public function setPpn(float $ppn): self
    {
        $this->ppn = $ppn;

        return $this;
    }

    public function getPph(): ?float
    {
        return $this->pph;
    }

    public function setPph(float $pph): self
    {
        $this->pph = $pph;

        return $this;
    }

    public function getBankFee(): ?float
    {
        return $this->bankFee;
    }

    public function setBankFee(float $bankFee): self
    {
        $this->bankFee = $bankFee;

        return $this;
    }

    public function getManagementFee(): ?float
    {
        return $this->managementFee;
    }

    public function setManagementFee(float $managementFee): self
    {
        $this->managementFee = $managementFee;

        return $this;
    }

    public function getOtherFee(): ?float
    {
        return $this->otherFee;
    }

    public function setOtherFee(float $otherFee): self
    {
        $this->otherFee = $otherFee;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new DateTime();

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
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getTotalProductPrice(): ?float
    {
        return $this->totalProductPrice;
    }

    public function setTotalProductPrice(float $totalProductPrice): self
    {
        $this->totalProductPrice = $totalProductPrice;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(?float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getLogs(): ?array
    {
        return $this->logs;
    }

    public function setLogs($user): self
    {
        $userData = [
            'id' => $user->getId(),
            'username' => $user->getUserName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phoneNumber' => $user->getPhoneNumber(),
        ];

        $logs['timestamp'] = (new DateTime(''))->format('d/m/Y H:i:s');
        $logs['status'] = $this->getStatus();
        $logs['admin'] = $userData;

        if (empty($this->logs)) {
            $this->logs = [$logs];
        }else {
            $this->logs = array_merge($this->getLogs(), [$logs]);
        }

        return $this;
    }

    public function getPersentasePpn(): ?float
    {
        return $this->persentase_ppn;
    }

    public function setPersentasePpn(float $persentase_ppn): self
    {
        $this->persentase_ppn = $persentase_ppn;

        return $this;
    }

    public function getPersentasePph(): ?float
    {
        return $this->persentase_pph;
    }

    public function setPersentasePph(float $persentase_pph): self
    {
        $this->persentase_pph = $persentase_pph;

        return $this;
    }

    public function getPersentaseBank(): ?float
    {
        return $this->persentase_bank;
    }

    public function setPersentaseBank(float $persentase_bank): self
    {
        $this->persentase_bank = $persentase_bank;

        return $this;
    }

    public function getPersentaseManagement(): ?float
    {
        return $this->persentase_management;
    }

    public function setPersentaseManagement(float $persentase_management): self
    {
        $this->persentase_management = $persentase_management;

        return $this;
    }

    public function getPersentaseOther(): ?float
    {
        return $this->persentase_other;
    }

    public function setPersentaseOther(float $persentase_other): self
    {
        $this->persentase_other = $persentase_other;

        return $this;
    }

    public function getPaymentProof(): ?string
    {
        return $this->payment_proof;
    }

    public function setPaymentProof(?string $payment_proof): self
    {
        $this->payment_proof = $payment_proof;

        return $this;
    }

    public function getRekeningName(): ?string
    {
        return $this->rekening_name;
    }

    public function setRekeningName(?string $rekening_name): self
    {
        $this->rekening_name = $rekening_name;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bank_name;
    }

    public function setBankName(?string $bank_name): self
    {
        $this->bank_name = $bank_name;

        return $this;
    }

    public function getNomorRekening(): ?string
    {
        return $this->nomor_rekening;
    }

    public function setNomorRekening(?string $nomor_rekening): self
    {
        $this->nomor_rekening = $nomor_rekening;

        return $this;
    }

    public function getOrderShippingPrice(): ?float
    {
        return $this->order_shipping_price;
    }

    public function setOrderShippingPrice(?float $order_shipping_price): self
    {
        $this->order_shipping_price = $order_shipping_price;

        return $this;
    }

    public function getStatusChangeTime(): ?\DateTimeInterface
    {
        return $this->statusChangeTime;
    }

    public function setStatusChangeTime(): self
    {
        $this->statusChangeTime = new DateTime();

        return $this;
    }
}
