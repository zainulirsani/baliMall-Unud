<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="`order`", indexes={@ORM\Index(name="order_shared_id_idx", columns={"shared_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\OrderRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="invoice", message="order.duplicate_invoice")
 */
class Order extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=100)
     */
    private $invoice;

    /**
     * Many Orders <==> One Store (as seller).
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="asSeller", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="store_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $seller;

    /**
     * Many Orders <==> One User (as buyer).
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="asBuyer", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $buyer;

    /**
     * Format: $buyer-$seller-random_string
     * @ORM\Column(type="string", nullable=true)
     */
    private $sharedId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sharedInvoice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $total;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $totalBackup;

    /**
     * @ORM\Column(type="string", length=50, options={"default": "pending"})
     * @Assert\Length(max=50)
     * @Assert\Choice({"cancel", "pending", "pending_payment", "payment_process", "paid", "confirmed", "processed", "shipped", "received", "returned", "finished"})
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $shippingCourier;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $shippingService;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $shippingPrice;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     */
    private $shippingPriceBackup;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $trackingCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $taxDocumentEmail;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $taxDocumentPhone;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $taxDocumentFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $address;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $address_note;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $addressUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $addressMap;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=8, nullable=true)
     */
    private $addressLat;

    /**
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $addressLng;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $mapPlaceId;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Assert\Length(min=5, max=5)
     */
    private $postCode;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $city;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $cityId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $district;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $districtId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $province;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $provinceId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $country;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $countryId;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $isB2gTransaction;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $bastFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $deliveryPaperFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $taxInvoiceFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $invoiceFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $chatRoomId;

    /**
     * @ORM\Column(type="string", options={"default": "none"})
     * @Assert\Choice({"none", "pending", "fail", "finish"})
     */
    private $negotiationStatus;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $executionTime;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $jobPackageName;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $fiscalYear;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sourceOfFund;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $budgetCeiling;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $receiptFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $workOrderLetterFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $qrisBillNumber;

    /**
     * One Order <==> Many Order Products.
     * @ORM\OneToMany(targetEntity="App\Entity\OrderProduct", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $orderProducts;

    /**
     * One Order <==> One Order Payment.
     * @ORM\OneToOne(targetEntity="App\Entity\OrderPayment", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $payment;

    /**
     * One Order <==> Many Order Negotiations.
     * @ORM\OneToMany(targetEntity="App\Entity\OrderNegotiation", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $orderNegotiations;

    /**
     * One Order <==> One Order Complaint.
     * @ORM\OneToOne(targetEntity="App\Entity\OrderComplaint", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $complaint;

    /**
     * One Order <==> Many Product Reviews.
     * @ORM\OneToMany(targetEntity="App\Entity\ProductReview", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $productReviews;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @Assert\NotBlank()
     */
    private $tnc;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $gosendBookingId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $dokuInvoiceNumber;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $midtransId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $withholdingTaxSlipFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tokoDaringReportStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $work_unit_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $institution_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $budget_account;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_nip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_nip;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_type;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $workUnit = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $spk_letter;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $handover_letter;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $handover_certificate;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cancellationStatus;

     /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $inspection_document;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $erzapOrderReport;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_payment_method;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $statusChangeTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $djpReportStatus;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tax_document_npwp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unit_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unit_pic;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unit_email;

    /**
     * @ORM\Column(type="text", length=255, nullable=true)
     */
    private $unit_note;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $state_img;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $shipped_product_img;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $self_courier_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $self_courier_position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $self_courier_address;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cancel_reason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shipped_method;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cancel_status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $unit_telp;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $unit_address;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\OrderShippedFile", mappedBy="order", fetch="EXTRA_LAZY")
     */
    private $orderShippedFiles;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $self_courier_telp;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $djp_response_order;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $djp_response_shipping;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $user_cancel_order;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $shipped_at;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ppk_telp;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_telp;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isApprovedPPK;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $receivedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sendAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $ppkId;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $treasurerId;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isApprovedOrderPPK;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $spkFile;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $taxEBilling;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $taxType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $treasurer_pph;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $treasurerPphNominal;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isRequestFakturPajak;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $bapdFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $other_document;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     */
    private $treasurer_ppn;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $otherPphName;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isOtherPph;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $other_ppn_name;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $other_ppn_persentase;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $is_other_ppn;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $treasurer_ppn_nominal;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $other_document_name;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $statusRating;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $status_approve_ppk;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $satkerId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $rupCode;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $cancel_file;

    /**
     * @ORM\OneToMany(targetEntity=BpdCc::class, mappedBy="orders")
     */
    private $bpdCcs;
    
    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $batch_nego;

    /**
     * @ORM\Column(type="string", options={"default": "partial"})
     */
    private $type_order = 'partial';

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $master_id;

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $batch;

    /**
     * @ORM\OneToMany(targetEntity=DocumentApproval::class, mappedBy="order_id", orphanRemoval=true)
     */
    private $documentApprovals;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    private $shipping_type;


    /**
     * @return mixed
     */
    public function getGosendBookingId()
    {
        return $this->gosendBookingId;
    }

    /**
     * @param mixed $gosendBookingId
     */
    public function setGosendBookingId($gosendBookingId): void
    {
        $this->gosendBookingId = $gosendBookingId;
    }

    public function __construct()
    {
        $this->status = 'pending';
        $this->isB2gTransaction = false;
        $this->negotiationStatus = 'none';
        $this->orderProducts = new ArrayCollection();
        $this->orderNegotiations = new ArrayCollection();
        $this->productReviews = new ArrayCollection();
        $this->orderShippedFiles = new ArrayCollection();
        $this->bpdCcs = new ArrayCollection();
        $this->documentApprovals = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;

    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice(string $invoice): void
    {
        $this->invoice = $invoice;
    }

    public function getSeller(): ?Store
    {
        return $this->seller;
    }

    public function setSeller(?Store $seller): void
    {
        $this->seller = $seller;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): void
    {
        $this->buyer = $buyer;
    }

    public function getSharedId()
    {
        return $this->sharedId;
    }

    public function setSharedId(?string $sharedId): void
    {
        $this->sharedId = $sharedId;
    }

    public function getSharedInvoice()
    {
        return $this->sharedInvoice;
    }

    public function setSharedInvoice(?string $sharedInvoice): void
    {
        $this->sharedInvoice = $sharedInvoice;
    }

    public function getTotal()
    {
        return $this->total;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }

    public function getTotalBackup()
    {
        return $this->totalBackup;
    }

    public function setTotalBackup(float $totalBackup): void
    {
        $this->totalBackup = $totalBackup;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getShippingCourier()
    {
        return $this->shippingCourier;
    }

    public function setShippingCourier(?string $shippingCourier): void
    {
        $this->shippingCourier = $shippingCourier;
    }

    public function getShippingService()
    {
        return $this->shippingService;
    }

    public function setShippingService(?string $shippingService): void
    {
        $this->shippingService = $shippingService;
    }

    public function getShippingPrice()
    {
        return $this->shippingPrice;
    }

    public function setShippingPrice(float $shippingPrice): void
    {
        $this->shippingPrice = $shippingPrice;
    }

    public function getShippingPriceBackup()
    {
        return $this->shippingPriceBackup;
    }

    public function setShippingPriceBackup(float $shippingPriceBackup): void
    {
        $this->shippingPriceBackup = $shippingPriceBackup;
    }

    public function getTrackingCode()
    {
        return $this->trackingCode;
    }

    public function setTrackingCode(?string $trackingCode): void
    {
        $this->trackingCode = $trackingCode;
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
        $this->email = $email;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getTaxDocumentEmail()
    {
        return $this->taxDocumentEmail;
    }

    public function setTaxDocumentEmail(?string $taxDocumentEmail): void
    {
        $this->taxDocumentEmail = $taxDocumentEmail;
    }

    public function getTaxDocumentPhone()
    {
        return $this->taxDocumentPhone;
    }

    public function setTaxDocumentPhone(?string $taxDocumentPhone): void
    {
        $this->taxDocumentPhone = $taxDocumentPhone;
    }

    public function getTaxDocumentFile()
    {
        return $this->taxDocumentFile;
    }

    public function setTaxDocumentFile(?string $taxDocumentFile): void
    {
        $this->taxDocumentFile = $taxDocumentFile;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getAddressNote()
    {
        return $this->address_note;
    }

    public function setAddressNote(?string $address_note): void
    {
        $this->address_note = $address_note;
    }

    public function getAddressUrl()
    {
        return $this->addressUrl;
    }

    public function setAddressUrl(?string $addressUrl): void
    {
        $this->addressUrl = $addressUrl;
    }

    public function getAddressMap()
    {
        return $this->addressMap;
    }

    public function setAddressMap(?string $addressMap): void
    {
        $this->addressMap = $addressMap;
    }

    public function getAddressLat()
    {
        return $this->addressLat;
    }

    public function setAddressLat(?float $addressLat): void
    {
        $this->addressLat = $addressLat;
    }

    public function getAddressLng()
    {
        return $this->addressLng;
    }

    public function setAddressLng(?float $addressLng): void
    {
        $this->addressLng = $addressLng;
    }

    public function getMapPlaceId()
    {
        return $this->mapPlaceId;
    }

    public function setMapPlaceId(?string $mapPlaceId): void
    {
        $this->mapPlaceId = $mapPlaceId;
    }

    public function getPostCode()
    {
        return $this->postCode;
    }

    public function setPostCode(?string $postCode): void
    {
        $this->postCode = $postCode;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCityId()
    {
        return $this->cityId;
    }

    public function setCityId(int $cityId = 0): void
    {
        $this->cityId = $cityId;
    }

    public function getDistrict()
    {
        return $this->district;
    }

    public function setDistrict(?string $district): void
    {
        $this->district = $district;
    }

    public function getDistrictId()
    {
        return $this->districtId;
    }

    public function setDistrictId(int $districtId = 0): void
    {
        $this->districtId = $districtId;
    }

    public function getProvince()
    {
        return $this->province;
    }

    public function setProvince(?string $province): void
    {
        $this->province = $province;
    }

    public function getProvinceId()
    {
        return $this->provinceId;
    }

    public function setProvinceId(int $provinceId = 0): void
    {
        $this->provinceId = $provinceId;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }

    public function getCountryId()
    {
        return $this->countryId;
    }

    public function setCountryId(int $countryId = 0): void
    {
        $this->countryId = $countryId;
    }

    public function getIsB2gTransaction(): bool
    {
        return $this->isB2gTransaction;
    }

    public function setIsB2gTransaction(bool $isB2gTransaction): void
    {
        $this->isB2gTransaction = $isB2gTransaction;
    }

    public function getBastFile()
    {
        return $this->bastFile;
    }

    public function setBastFile(?string $bastFile): void
    {
        $this->bastFile = $bastFile;
    }

    public function getDeliveryPaperFile()
    {
        return $this->deliveryPaperFile;
    }

    public function setDeliveryPaperFile(?string $deliveryPaperFile): void
    {
        $this->deliveryPaperFile = $deliveryPaperFile;
    }

    public function getTaxInvoiceFile()
    {
        return $this->taxInvoiceFile;
    }

    public function setTaxInvoiceFile(?string $taxInvoiceFile): void
    {
        $this->taxInvoiceFile = $taxInvoiceFile;
    }

    public function getInvoiceFile()
    {
        return $this->invoiceFile;
    }

    public function setInvoiceFile(?string $invoiceFile): void
    {
        $this->invoiceFile = $invoiceFile;
    }

    public function getChatRoomId()
    {
        return $this->chatRoomId;
    }

    public function setChatRoomId(?string $chatRoomId): void
    {
        $this->chatRoomId = $chatRoomId;
    }

    public function getNegotiationStatus(): string
    {
        return $this->negotiationStatus;
    }

    public function setNegotiationStatus(string $negotiationStatus): void
    {
        $this->negotiationStatus = $negotiationStatus;
    }

    public function getExecutionTime()
    {
        return $this->executionTime;
    }

    public function setExecutionTime(string $executionTime): void
    {
        $this->executionTime = $executionTime;
    }

    public function getJobPackageName()
    {
        return $this->jobPackageName;
    }

    public function setJobPackageName(?string $jobPackageName): void
    {
        $this->jobPackageName = $jobPackageName;
    }

    public function getFiscalYear()
    {
        return $this->fiscalYear;
    }

    public function setFiscalYear(?string $fiscalYear): void
    {
        $this->fiscalYear = $fiscalYear;
    }

    public function getSourceOfFund()
    {
        return $this->sourceOfFund;
    }

    public function setSourceOfFund(?string $sourceOfFund): void
    {
        $this->sourceOfFund = $sourceOfFund;
    }

    public function getBudgetCeiling()
    {
        return $this->budgetCeiling;
    }

    public function setBudgetCeiling(?string $budgetCeiling): void
    {
        $this->budgetCeiling = $budgetCeiling;
    }

    public function getReceiptFile()
    {
        return $this->receiptFile;
    }

    public function setReceiptFile(?string $receiptFile): void
    {
        $this->receiptFile = $receiptFile;
    }

    public function getWorkOrderLetterFile()
    {
        return $this->workOrderLetterFile;
    }

    public function setWorkOrderLetterFile(?string $workOrderLetterFile): void
    {
        $this->workOrderLetterFile = $workOrderLetterFile;
    }

    public function getQRISBillNumber()
    {
        return $this->qrisBillNumber;
    }

    public function setQRISBillNumber(?string $qrisBillNumber): void
    {
        $this->qrisBillNumber = $qrisBillNumber;
    }

    /**
     * @return Collection|OrderProduct[]
     */
    public function getOrderProducts()
    {
        return $this->orderProducts;
    }

    /**
     * @return Collection|OrderNegotiation[]
     */
    public function getOrderNegotiations()
    {
        return $this->orderNegotiations;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function getComplaint()
    {
        return $this->complaint;
    }

    /**
     * @return Collection|ProductReview[]
     */
    public function getProductReviews()
    {
        return $this->productReviews;
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

    public function getTnc()
    {
        return $this->tnc;
    }

    public function setTnc($tnc): void
    {
        $this->tnc = $tnc;
    }

    public function getDokuInvoiceNumber(): ?string
    {
        return $this->dokuInvoiceNumber;
    }

    public function setDokuInvoiceNumber(?string $dokuInvoiceNumber): self
    {
        $this->dokuInvoiceNumber = $dokuInvoiceNumber;

        return $this;
    }

    public function getMidtransId(): ?int
    {
        return $this->midtransId;
    }

    public function setMidtransId(?int $midtransId): self
    {
        $this->midtransId = $midtransId;

        return $this;
    }

    public function getWithholdingTaxSlipFile(): ?string
    {
        return $this->withholdingTaxSlipFile;
    }

    public function setWithholdingTaxSlipFile(?string $withholdingTaxSlipFile): self
    {
        $this->withholdingTaxSlipFile = $withholdingTaxSlipFile;

        return $this;
    }

    public function getTokoDaringReportStatus(): ?string
    {
        return $this->tokoDaringReportStatus;
    }

    public function setTokoDaringReportStatus(?string $tokoDaringReportStatus): self
    {
        $this->tokoDaringReportStatus = $tokoDaringReportStatus;

        return $this;
    }

    public function getWorkUnitName(): ?string
    {
        return $this->work_unit_name;
    }

    public function setWorkUnitName(?string $work_unit_name): self
    {
        $this->work_unit_name = $work_unit_name;

        return $this;
    }

    public function getInstitutionName(): ?string
    {
        return $this->institution_name;
    }

    public function setInstitutionName(?string $institution_name): self
    {
        $this->institution_name = $institution_name;

        return $this;
    }

    public function getBudgetAccount(): ?string
    {
        return $this->budget_account;
    }

    public function setBudgetAccount(?string $budget_account): self
    {
        $this->budget_account = $budget_account;

        return $this;
    }

    public function getPpkName(): ?string
    {
        return $this->ppk_name;
    }

    public function setPpkName(?string $ppk_name): self
    {
        $this->ppk_name = $ppk_name;

        return $this;
    }

    public function getPpkNip(): ?string
    {
        return $this->ppk_nip;
    }

    public function setPpkNip(?string $ppk_nip): self
    {
        $this->ppk_nip = $ppk_nip;

        return $this;
    }

    public function getTreasurerName(): ?string
    {
        return $this->treasurer_name;
    }

    public function setTreasurerName(?string $treasurer_name): self
    {
        $this->treasurer_name = $treasurer_name;

        return $this;
    }

    public function getTreasurerNip(): ?string
    {
        return $this->treasurer_nip;
    }

    public function setTreasurerNip(?string $treasurer_nip): self
    {
        $this->treasurer_nip = $treasurer_nip;

        return $this;
    }

    public function getPpkType(): ?string
    {
        return $this->ppk_type;
    }

    public function setPpkType(?string $ppk_type): self
    {
        $this->ppk_type = $ppk_type;

        return $this;
    }

    public function getTreasurerType(): ?string
    {
        return $this->treasurer_type;
    }

    public function setTreasurerType(?string $treasurer_type): self
    {
        $this->treasurer_type = $treasurer_type;

        return $this;
    }

    public function getWorkUnit(): ?array
    {
        return $this->workUnit;
    }

    public function setWorkUnit(User $workUnit): self
    {
//        $data = [
//            'id' => $workUnit->getId(),
//            'owner_id' => $workUnit->getOwner()->getId(),
//            'fullname' => $workUnit->getFullname(),
//            'role' => $workUnit->getRole(),
//            'address' => $workUnit->getAddress(),
//            'phone' => $workUnit->getPhone(),
//            'workunit' => $workUnit->getWorkUnit(),
//        ];

        $data = [];

        $this->workUnit = $data;

        return $this;
    }

    public function getSpkLetter(): ?string
    {
        return $this->spk_letter;
    }

    public function setSpkLetter(?string $spk_letter): self
    {
        $this->spk_letter = $spk_letter;

        return $this;
    }

    public function getHandoverLetter(): ?string
    {
        return $this->handover_letter;
    }

    public function setHandoverLetter(?string $handover_letter): self
    {
        $this->handover_letter = $handover_letter;

        return $this;
    }

    public function getHandoverCertificate(): ?string
    {
        return $this->handover_certificate;
    }

    public function setHandoverCertificate(?string $handover_certificate): self
    {
        $this->handover_certificate = $handover_certificate;

        return $this;
    }

    public function getCancellationStatus(): ?string
    {
        return $this->cancellationStatus;
    }

    public function setCancellationStatus(?string $cancellationStatus): self
    {
        $this->cancellationStatus = $cancellationStatus;

        return $this;
    }

    public function getInspectionDocument(): ?string
    {
        return $this->inspection_document;
    }

    public function setInspectionDocument(?string $inspection_document): self
    {
        $this->inspection_document = $inspection_document;

        return $this;
    }

    public function getErzapOrderReport(): ?string
    {
        return $this->erzapOrderReport;
    }

    public function setErzapOrderReport(?string $erzapOrderReport): self
    {
        $this->erzapOrderReport = $erzapOrderReport;

        return $this;
    }

    public function getPpkPaymentMethod(): ?string
    {
        return $this->ppk_payment_method;
    }

    public function setPpkPaymentMethod(?string $ppk_payment_method): self
    {
        $this->ppk_payment_method = $ppk_payment_method;

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

    public function getDjpReportStatus(): ?string
    {
        return $this->djpReportStatus;
    }

    public function setDjpReportStatus(?string $djpReportStatus): self
    {
        $this->djpReportStatus = $djpReportStatus;

        return $this;
    }

    public function getTaxDocumentNpwp(): ?string
    {
        return $this->tax_document_npwp;
    }

    public function setTaxDocumentNpwp(?string $tax_document_npwp): self
    {
        $this->tax_document_npwp = $tax_document_npwp;

        return $this;
    }

    public function getUnitName(): ?string
    {
        return $this->unit_name;
    }

    public function setUnitName(?string $unit_name): self
    {
        $this->unit_name = $unit_name;

        return $this;
    }

    public function getUnitNote(): ?string
    {
        return $this->unit_note;
    }

    public function setUnitNote(?string $unit_note): self
    {
        $this->unit_note = $unit_note;

        return $this;
    }
    
    public function getCancelReason(): ?string
    {
        return $this->cancel_reason;
    }

    public function setCancelReason(?string $cancel_reason): self
    {
        $this->cancel_reason = $cancel_reason;

        return $this;
    }

    public function getUnitPic(): ?string
    {
        return $this->unit_pic;
    }

    public function setUnitPic(?string $unit_pic): self
    {
        $this->unit_pic = $unit_pic;
        
        return $this;
    }

    public function getCancelStatus(): ?string
    {
        return $this->cancel_status;
    }

    public function setCancelStatus(?string $cancel_status): self
    {
        $this->cancel_status = $cancel_status;

        return $this;
    }

    public function getUnitEmail(): ?string
    {
        return $this->unit_email;
    }

    public function setUnitEmail(?string $unit_email): self
    {
        $this->unit_email = $unit_email;

        return $this;
    }

    public function getStateImg(): ?string
    {
        return $this->state_img;
    }

    public function setStateImg(?string $state_img): self
    {
        $this->state_img = $state_img;

        return $this;
    }

    public function getShippedProductImg(): ?string
    {
        return $this->shipped_product_img;
    }

    public function setShippedProductImg(?string $shipped_product_img): self
    {
        $this->shipped_product_img = $shipped_product_img;

        return $this;
    }

    public function getSelfCourierName(): ?string
    {
        return $this->self_courier_name;
    }

    public function setSelfCourierName(?string $self_courier_name): self
    {
        $this->self_courier_name = $self_courier_name;

        return $this;
    }

    public function getSelfCourierPosition(): ?string
    {
        return $this->self_courier_position;
    }

    public function setSelfCourierPosition(?string $self_courier_position): self
    {
        $this->self_courier_position = $self_courier_position;

        return $this;
    }

    public function getSelfCourierAddress(): ?string
    {
        return $this->self_courier_address;
    }

    public function setSelfCourierAddress(?string $self_courier_address): self
    {
        $this->self_courier_address = $self_courier_address;

        return $this;
    }

    public function getShippedMethod(): ?string
    {
        return $this->shipped_method;
    }

    public function setShippedMethod(?string $shipped_method): self
    {
        $this->shipped_method = $shipped_method;

        return $this;
    }

    public function getUnitTelp(): ?string
    {
        return $this->unit_telp;
    }

    public function setUnitTelp(?string $unit_telp): self
    {
        $this->unit_telp = $unit_telp;

        return $this;
    }

    public function getUnitAddress(): ?string
    {
        return $this->unit_address;
    }

    public function setUnitAddress(?string $unit_address): self
    {
        $this->unit_address = $unit_address;

        return $this;
    }

    /**
     * @return Collection|OrderShippedFile[]
     */
    public function getOrderShippedFiles(): Collection
    {
        return $this->orderShippedFiles;
    }

    public function addOrderShippedFile(OrderShippedFile $orderShippedFile): self
    {
        if (!$this->orderShippedFiles->contains($orderShippedFile)) {
            $this->orderShippedFiles[] = $orderShippedFile;
            $orderShippedFile->setOrder($this);
        }

        return $this;
    }

    public function removeOrderShippedFile(OrderShippedFile $orderShippedFile): self
    {
        if ($this->orderShippedFiles->removeElement($orderShippedFile)) {
            // set the owning side to null (unless already changed)
            if ($orderShippedFile->getOrder() === $this) {
                $orderShippedFile->setOrder(null);
            }
        }

        return $this;
    }

    public function getSelfCourierTelp(): ?string
    {
        return $this->self_courier_telp;
    }

    public function setSelfCourierTelp(?string $self_courier_telp): self
    {
        $this->self_courier_telp = $self_courier_telp;

        return $this;
    }

    public function getDjpResponseOrder(): ?string
    {
        return $this->djp_response_order;
    }

    public function setDjpResponseOrder(?string $djp_response_order): self
    {
        $this->djp_response_order = $djp_response_order;

        return $this;
    }

    public function getDjpResponseShipping(): ?string
    {
        return $this->djp_response_shipping;
    }

    public function setDjpResponseShipping(?string $djp_response_shipping): self
    {
        $this->djp_response_shipping = $djp_response_shipping;

        return $this;
    }

    public function getShippedAt(): ?\DateTimeInterface
    {
        return $this->shipped_at;
    }

    public function setShippedAt(): self
    {
        $this->shipped_at = new DateTime();
    
        return $this;
    }

    public function getUserCancelOrder(): ?string
    {
        return $this->user_cancel_order;
    }

    public function setUserCancelOrder(?string $user_cancel_order): self
    {
        $this->shipped_at = new DateTime();;

        return $this;
    }

    public function getPpkEmail(): ?string
    {
        return $this->ppk_email;
    }

    public function setPpkEmail(?string $ppk_email): self
    {
        $this->ppk_email = $ppk_email;

        return $this;
    }

    public function getTreasurerEmail(): ?string
    {
        return $this->treasurer_email;
    }

    public function setTreasurerEmail(?string $treasurer_email): self
    {
        $this->treasurer_email = $treasurer_email;

        return $this;
    }

    public function getPpkTelp(): ?string
    {
        return $this->ppk_telp;
    }

    public function setPpkTelp(?string $ppk_telp): self
    {
        $this->ppk_telp = $ppk_telp;

        return $this;
    }

    public function getTreasurerTelp(): ?string
    {
        return $this->treasurer_telp;
    }

    public function setTreasurerTelp(?string $treasurer_telp): self
    {
        $this->treasurer_telp = $treasurer_telp;

        return $this;
    }

    public function getIsApprovedPPK(): ?bool
    {
        return $this->isApprovedPPK;
    }

    public function setIsApprovedPPK(?bool $isApprovedPPK): self
    {
        $this->isApprovedPPK = $isApprovedPPK;

        return $this;
    }

    public function getReceivedAt(): ?\DateTimeInterface
    {
        return $this->receivedAt;
    }

    public function setReceivedAt($receivedAt = null): self
    {
        $this->receivedAt = !is_null($receivedAt) ? new DateTime($receivedAt)  : new DateTime();

        return $this;
    }

    public function getSendAt(): ?\DateTimeInterface
    {
        return $this->sendAt;
    }

    public function setSendAt($sendAt = null): self
    {
        $this->sendAt =  new DateTime($sendAt);

        return $this;
    }
    
    public function getPpkId(): ?int
    {
        return $this->ppkId;
    }

    public function setPpkId(?int $ppkId): self
    {
        $this->ppkId = $ppkId;

        return $this;
    }

    public function getSpkFile(): ?string
    {
        return $this->spkFile;
    }

    public function setSpkFile(?string $spkFile): self
    {
        $this->spkFile = $spkFile;

        return $this;
    }
    
    public function getTreasurerId(): ?int
    {
        return $this->treasurerId;
    }

    public function setTreasurerId(?int $treasurerId): self
    {
        $this->treasurerId = $treasurerId;

        return $this;
    }

    public function getBapdFile(): ?string
    {
        return $this->bapdFile;
    }

    public function setBapdFile(?string $bapdFile): self
    {
        $this->bapdFile = $bapdFile;

        return $this;
    }
    
    public function getIsApprovedOrderPPK(): ?bool
    {
        return $this->isApprovedOrderPPK;
    }

    public function setIsApprovedOrderPPK(?bool $isApprovedOrderPPK): self
    {
        $this->isApprovedOrderPPK = $isApprovedOrderPPK;

        return $this;
    }

    public function getTaxEBilling(): ?string
    {
        return $this->taxEBilling;
    }

    public function setTaxEBilling(?string $taxEBilling): self
    {
        $this->taxEBilling = $taxEBilling;

        return $this;
    }

    public function getTaxType(): ?string
    {
        return $this->taxType;
    }

    public function setTaxType(?string $taxType): self
    {
        $this->taxType = $taxType;

        return $this;
    }

    public function getTreasurerPph(): ?string
    {
        return $this->treasurer_pph;
    }

    public function setTreasurerPph(?string $treasurer_pph): self
    {
        $this->treasurer_pph = $treasurer_pph;

        return $this;
    }

    public function getTreasurerPphNominal(): ?string
    {
        return $this->treasurerPphNominal;
    }

    public function setTreasurerPphNominal(?string $treasurerPphNominal): self
    {
        $this->treasurerPphNominal = $treasurerPphNominal;

        return $this;
    }

    public function getIsRequestFakturPajak(): ?bool
    {
        return $this->isRequestFakturPajak;
    }

    public function setIsRequestFakturPajak(?bool $isRequestFakturPajak): self
    {
        $this->isRequestFakturPajak = $isRequestFakturPajak;

        return $this;
    }

    public function getOtherDocument(): ?string
    {
        return $this->other_document;
    }

    public function setOtherDocument(?string $other_document): self
    {
        $this->other_document = $other_document;

        return $this;
    }

    public function getTreasurerPpn(): ?string
    {
        return $this->treasurer_ppn;
    }

    public function setTreasurerPpn(?string $treasurer_ppn): self
    {
        $this->treasurer_ppn = $treasurer_ppn;

        return $this;
    }

    public function getOtherPphName(): ?string
    {
        return $this->otherPphName;
    }

    public function setOtherPphName(?string $otherPphName): self
    {
        $this->otherPphName = $otherPphName;

        return $this;
    }

    public function getIsOtherPph(): ?bool
    {
        return $this->isOtherPph;
    }

    public function setIsOtherPph(?bool $isOtherPph): self
    {
        $this->isOtherPph = $isOtherPph;

        return $this;
    }

    public function getOtherPpnName(): ?string
    {
        return $this->other_ppn_name;
    }

    public function setOtherPpnName(?string $other_ppn_name): self
    {
        $this->other_ppn_name = $other_ppn_name;

        return $this;
    }

    public function getOtherPpnPersentase(): ?string
    {
        return $this->other_ppn_persentase;
    }

    public function setOtherPpnPersentase(?string $other_ppn_persentase): self
    {
        $this->other_ppn_persentase = $other_ppn_persentase;

        return $this;
    }

    public function getIsOtherPpn(): ?bool
    {
        return $this->is_other_ppn;
    }

    public function setIsOtherPpn(?bool $is_other_ppn): self
    {
        $this->is_other_ppn = $is_other_ppn;

        return $this;
    }

    public function getTreasurerPpnNominal(): ?string
    {
        return $this->treasurer_ppn_nominal;
    }

    public function setTreasurerPpnNominal(?string $treasurer_ppn_nominal): self
    {
        $this->treasurer_ppn_nominal = $treasurer_ppn_nominal;

        return $this;
    }

    public function getOtherDocumentName(): ?string
    {
        return $this->other_document_name;
    }

    public function setOtherDocumentName(?string $other_document_name): self
    {
        $this->other_document_name = $other_document_name;

        return $this;
    }

    public function getStatusRating(): ?string
    {
        return $this->statusRating;
    }

    public function setStatusRating(?string $statusRating): self
    {
        $this->statusRating = $statusRating;

        return $this;
    }

    public function getStatusApprovePpk(): ?string
    {
        return $this->status_approve_ppk;
    }

    public function setStatusApprovePpk(?string $status_approve_ppk): self
    {
        $this->status_approve_ppk = $status_approve_ppk;

        return $this;
    }

    public function getSatkerId(): ?int
    {
        return $this->satkerId;
    }

    public function setSatkerId(?int $satkerId): self
    {
        $this->satkerId = $satkerId;

        return $this;
    }
    
    public function getRupCode(): ?string
    {
        return $this->rupCode;
    }

    public function setRupCode(?string $rupCode): self
    {
        $this->rupCode = $rupCode;

        return $this;
    }

    public function getCancelFile(): ?string
    {
        return $this->cancel_file;
    }

    public function setCancelFile(?string $cancel_file): self
    {
        $this->cancel_file = $cancel_file;

        return $this;
    }

    /**
     * @return Collection|BpdCc[]
     */
    public function getBpdCcs(): Collection
    {
        return $this->bpdCcs;
    }

    public function addBpdCc(BpdCc $bpdCc): self
    {
        if (!$this->bpdCcs->contains($bpdCc)) {
            $this->bpdCcs[] = $bpdCc;
            $bpdCc->setOrders($this);
        }

        return $this;
    }

    public function removeBpdCc(BpdCc $bpdCc): self
    {
        if ($this->bpdCcs->removeElement($bpdCc)) {
            // set the owning side to null (unless already changed)
            if ($bpdCc->getOrders() === $this) {
                $bpdCc->setOrders(null);
            }
        }
        return $this;
    }

    public function getBatchNego(): ?string
    {
        return $this->batch_nego;
    }

    public function setBatchNego(?string $batch_nego): self
    {
        $this->batch_nego = $batch_nego;

        return $this;
    }

    public function getTypeOrder(): ?string
    {
        return $this->type_order;
    }

    public function setTypeOrder(string $type_order): self
    {
        $this->type_order = $type_order;

        return $this;
    }

    public function getMasterId(): ?int
    {
        return $this->master_id;
    }

    public function setMasterId($master_id): self
    {
        $this->master_id = $master_id;

        return $this;
    }

    public function getBatch(): ?int
    {
        return $this->batch;
    }

    public function setBatch($batch): self
    {
        $this->batch = $batch;

        return $this;
    }

    /**
     * @return Collection<int, DocumentApproval>
     */
    public function getDocumentApprovals(): Collection
    {
        return $this->documentApprovals;
    }

    public function addDocumentApproval(DocumentApproval $documentApproval): self
    {
        if (!$this->documentApprovals->contains($documentApproval)) {
            $this->documentApprovals[] = $documentApproval;
            $documentApproval->setOrderId($this);
        }

        return $this;
    }

    public function removeDocumentApproval(DocumentApproval $documentApproval): self
    {
        if ($this->documentApprovals->removeElement($documentApproval)) {
            // set the owning side to null (unless already changed)
            if ($documentApproval->getOrderId() === $this) {
                $documentApproval->setOrderId(null);
            }
        }

        return $this;
    }

    public function getShippingType(): ?string
    {
        return $this->shipping_type;
    }

    public function setShippingType(?string $shipping_type): self
    {
        $this->shipping_type = $shipping_type;

        return $this;
    }
}
