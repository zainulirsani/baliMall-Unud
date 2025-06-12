<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="qris", indexes={
 *     @ORM\Index(name="qris_invoice_idx", columns={"invoice"}),
 *     @ORM\Index(name="qris_bill_number_idx", columns={"bill_number"}),
 *     @ORM\Index(name="qris_trx_id_idx", columns={"trx_id"}),
 *     @ORM\Index(name="qris_trx_status_idx", columns={"trx_status"}),
 *     @ORM\Index(name="qris_reference_number_idx", columns={"reference_number"}),
 *     @ORM\Index(name="qris_qr_id_idx", columns={"qr_id"}),
 *     @ORM\Index(name="qris_qr_status_idx", columns={"qr_status"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\QrisRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Qris extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * EDIT: This is now using `qris_bill_number` value from order table (previously using `shared_invoice` value)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice;

    /**
     * Should be the same as invoice value above, but the response from API is a bit different
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $billNumber;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $recordId;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $trxId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $trxDate;

    /**
     * Choices: SUCCEED, FAILED, REFUNDED, TO_REFUND
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $trxStatus;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $trxStatusDetail;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $referenceNumber;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $amount;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $tips;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $qrId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $qrValue;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $qrImage;

    /**
     * Choices: Belum Terbayar, Sudah Terbayar, Expired
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $qrStatus;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $nmid;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $mid;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $merchantName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $productCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $issuerName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $responseCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $mdrPercentage;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $netNominal;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $branchCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $refundDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $createdDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $expiredDate;

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
        $this->recordId = 0;
        $this->trxId = 0;
        $this->qrId = 0;
        $this->amount = 0.00;
        $this->tips = 0.00;
        $this->totalAmount = 0.00;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice($invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getBillNumber()
    {
        return $this->billNumber;
    }

    public function setBillNumber($billNumber): void
    {
        $this->billNumber = $billNumber;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId($recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getTrxId(): int
    {
        return $this->trxId;
    }

    public function setTrxId($trxId): void
    {
        $this->trxId = $trxId;
    }

    public function getTrxDate()
    {
        return $this->trxDate;
    }

    public function setTrxDate($trxDate): void
    {
        $this->trxDate = $trxDate;
    }

    public function getTrxStatus()
    {
        return $this->trxStatus;
    }

    public function setTrxStatus($trxStatus): void
    {
        $this->trxStatus = $trxStatus;
    }

    public function getTrxStatusDetail()
    {
        return $this->trxStatusDetail;
    }

    public function setTrxStatusDetail($trxStatusDetail): void
    {
        $this->trxStatusDetail = $trxStatusDetail;
    }

    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    public function setReferenceNumber($referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    public function getTips(): float
    {
        return $this->tips;
    }

    public function setTips($tips): void
    {
        $this->tips = $tips;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount($totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getQrId(): int
    {
        return $this->qrId;
    }

    public function setQrId($qrId): void
    {
        $this->qrId = $qrId;
    }

    public function getQrValue()
    {
        return $this->qrValue;
    }

    public function setQrValue($qrValue): void
    {
        $this->qrValue = $qrValue;
    }

    public function getQrImage()
    {
        return $this->qrImage;
    }

    public function setQrImage($qrImage): void
    {
        $this->qrImage = $qrImage;
    }

    public function getQrStatus()
    {
        return $this->qrStatus;
    }

    public function setQrStatus($qrStatus): void
    {
        $this->qrStatus = $qrStatus;
    }

    public function getNmid()
    {
        return $this->nmid;
    }

    public function setNmid($nmid): void
    {
        $this->nmid = $nmid;
    }

    public function getMid()
    {
        return $this->mid;
    }

    public function setMid($mid): void
    {
        $this->mid = $mid;
    }

    public function getMerchantName()
    {
        return $this->merchantName;
    }

    public function setMerchantName($merchantName): void
    {
        $this->merchantName = $merchantName;
    }

    public function getProductCode()
    {
        return $this->productCode;
    }

    public function setProductCode($productCode): void
    {
        $this->productCode = $productCode;
    }

    public function getIssuerName()
    {
        return $this->issuerName;
    }

    public function setIssuerName($issuerName): void
    {
        $this->issuerName = $issuerName;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function setResponseCode($responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getMdrPercentage()
    {
        return $this->mdrPercentage;
    }

    public function setMdrPercentage($mdrPercentage): void
    {
        $this->mdrPercentage = $mdrPercentage;
    }

    public function getNetNominal()
    {
        return $this->netNominal;
    }

    public function setNetNominal($netNominal): void
    {
        $this->netNominal = $netNominal;
    }

    public function getBranchCode()
    {
        return $this->branchCode;
    }

    public function setBranchCode($branchCode): void
    {
        $this->branchCode = $branchCode;
    }

    public function getRefundDate()
    {
        return $this->refundDate;
    }

    public function setRefundDate($refundDate): void
    {
        $this->refundDate = $refundDate;
    }

    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function setCreatedDate($createdDate): void
    {
        $this->createdDate = $createdDate;
    }

    public function getExpiredDate()
    {
        return $this->expiredDate;
    }

    public function setExpiredDate($expiredDate): void
    {
        $this->expiredDate = $expiredDate;
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
