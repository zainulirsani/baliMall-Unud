<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="virtual_account", indexes={
 *     @ORM\Index(name="virtual_account_invoice_idx", columns={"invoice"}),
 *     @ORM\Index(name="virtual_account_record_id_idx", columns={"record_id"}),
 *     @ORM\Index(name="virtual_account_bill_number_idx", columns={"bill_number"}),
 *     @ORM\Index(name="virtual_account_transaction_id_idx", columns={"transaction_id"}),
 *     @ORM\Index(name="virtual_account_reference_id_idx", columns={"reference_id"}),
 *     @ORM\Index(name="virtual_account_status_idx", columns={"status"}),
 *     @ORM\Index(name="virtual_account_paid_status_idx", columns={"paid_status"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\VirtualAccountRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class VirtualAccount extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * This is using `shared_invoice` value from `order` table
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoice;

    /**
     * This is using `recordId` field value from response
     * -- (recommendation is not to save/use this value)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $recordId;

    /**
     * This is using `noid` field when inserting data
     * -- (number only, length 12 digit? see OrderController::payWithChannel in `virtual-account` section to generate this value)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $billNumber;

    /**
     * This is using `ket_1_val` field when inserting data
     * -- (join value of multiple order ids and split by "|")
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $transactionId;

    /**
     * This is using `ket_2_val` field when inserting data
     * -- (uuid)
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $referenceId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $name;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, options={"default": "0.00"})
     */
    private $amount;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $institution;

    /**
     * This is using `tgl_upd` field value from response
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $paidDate;

    /**
     * This is using `sts_bayar` field value from response
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $paidStatus;

    /**
     * This is using `kd_user` field value from response
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $kdUser;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $response;

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
        $this->amount = 0.00;
        $this->paidStatus = '0';
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

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function setRecordId($recordId): void
    {
        $this->recordId = $recordId;
    }

    public function getBillNumber()
    {
        return $this->billNumber;
    }

    public function setBillNumber($billNumber): void
    {
        $this->billNumber = $billNumber;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getReferenceId()
    {
        return $this->referenceId;
    }

    public function setReferenceId($referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getInstitution()
    {
        return $this->institution;
    }

    public function setInstitution($institution): void
    {
        $this->institution = $institution;
    }

    public function getPaidDate()
    {
        return $this->paidDate;
    }

    public function setPaidDate(?string $paidDate): void
    {
        $this->paidDate = $paidDate;
    }

    public function getPaidStatus(): string
    {
        return $this->paidStatus;
    }

    public function setPaidStatus($paidStatus): void
    {
        $this->paidStatus = $paidStatus;
    }

    public function getKdUser()
    {
        return $this->kdUser;
    }

    public function setKdUser($kdUser): void
    {
        $this->kdUser = $kdUser;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response): void
    {
        $this->response = $response;
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
