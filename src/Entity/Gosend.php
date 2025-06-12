<?php

namespace App\Entity;

use App\Repository\GosendRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=GosendRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Gosend
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bookingId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderNo;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $driverId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $driverName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $driverPhone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $driverPhoto;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $totalPrice;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $receiverName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderCreatedTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderDispatchTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $orderArrivalTime;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerAddressName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerAddressDetail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $buyerAddressName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $buyerAddressDetail;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $storeOrderId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $insuranceDetails;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cancelDescription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bookingType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sellerNote;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $liveTrackingUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cancelledBy;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $driverPhone2;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $driverPhone3;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $totalDistance;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pickupEta;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $deliveryEta;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @return mixed
     */
    public function getLiveTrackingUrl()
    {
        return $this->liveTrackingUrl;
    }

    /**
     * @param mixed $liveTrackingUrl
     */
    public function setLiveTrackingUrl($liveTrackingUrl): void
    {
        $this->liveTrackingUrl = $liveTrackingUrl;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    public function setBookingId(?int $bookingId): self
    {
        $this->bookingId = $bookingId;

        return $this;
    }

    public function getOrderNo(): ?string
    {
        return $this->orderNo;
    }

    public function setOrderNo(?string $orderNo): self
    {
        $this->orderNo = $orderNo;

        return $this;
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

    public function getDriverId(): ?int
    {
        return $this->driverId;
    }

    public function setDriverId(?int $driverId): self
    {
        $this->driverId = $driverId;

        return $this;
    }

    public function getDriverName(): ?string
    {
        return $this->driverName;
    }

    public function setDriverName(?string $driverName): self
    {
        $this->driverName = $driverName;

        return $this;
    }

    public function getDriverPhone(): ?string
    {
        return $this->driverPhone;
    }

    public function setDriverPhone(?string $driverPhone): self
    {
        $this->driverPhone = $driverPhone;

        return $this;
    }

    public function getDriverPhoto(): ?string
    {
        return $this->driverPhoto;
    }

    public function setDriverPhoto(?string $driverPhoto): self
    {
        $this->driverPhoto = $driverPhoto;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getReceiverName(): ?string
    {
        return $this->receiverName;
    }

    public function setReceiverName(?string $receiverName): self
    {
        $this->receiverName = $receiverName;

        return $this;
    }

    public function getOrderCreatedTime(): ?string
    {
        return $this->orderCreatedTime;
    }

    public function setOrderCreatedTime(?string $orderCreatedTime): self
    {
        $this->orderCreatedTime = $orderCreatedTime;

        return $this;
    }

    public function getOrderDispatchTime(): ?string
    {
        return $this->orderDispatchTime;
    }

    public function setOrderDispatchTime(?string $orderDispatchTime): self
    {
        $this->orderDispatchTime = $orderDispatchTime;

        return $this;
    }

    public function getOrderArrivalTime(): ?string
    {
        return $this->orderArrivalTime;
    }

    public function setOrderArrivalTime(?string $orderArrivalTime): self
    {
        $this->orderArrivalTime = $orderArrivalTime;

        return $this;
    }

    public function getSellerAddressName(): ?string
    {
        return $this->sellerAddressName;
    }

    public function setSellerAddressName(?string $sellerAddressName): self
    {
        $this->sellerAddressName = $sellerAddressName;

        return $this;
    }

    public function getSellerAddressDetail(): ?string
    {
        return $this->sellerAddressDetail;
    }

    public function setSellerAddressDetail(?string $sellerAddressDetail): self
    {
        $this->sellerAddressDetail = $sellerAddressDetail;

        return $this;
    }

    public function getBuyerAddressName(): ?string
    {
        return $this->buyerAddressName;
    }

    public function setBuyerAddressName(?string $buyerAddressName): self
    {
        $this->buyerAddressName = $buyerAddressName;

        return $this;
    }

    public function getBuyerAddressDetail(): ?string
    {
        return $this->buyerAddressDetail;
    }

    public function setBuyerAddressDetail(?string $buyerAddressDetail): self
    {
        $this->buyerAddressDetail = $buyerAddressDetail;

        return $this;
    }

    public function getStoreOrderId(): ?int
    {
        return $this->storeOrderId;
    }

    public function setStoreOrderId(?int $storeOrderId): self
    {
        $this->storeOrderId = $storeOrderId;

        return $this;
    }

    public function getInsuranceDetails(): ?string
    {
        return json_decode($this->insuranceDetails, true);
    }

    public function setInsuranceDetails(?string $insuranceDetails): self
    {
        $this->insuranceDetails = $insuranceDetails;

        return $this;
    }

    public function getCancelDescription(): ?string
    {
        return $this->cancelDescription;
    }

    public function setCancelDescription(?string $cancelDescription): self
    {
        $this->cancelDescription = $cancelDescription;

        return $this;
    }

    public function getBookingType(): ?string
    {
        return $this->bookingType;
    }

    public function setBookingType(?string $bookingType): self
    {
        $this->bookingType = $bookingType;

        return $this;
    }

    public function getSellerNote(): ?string
    {
        return $this->sellerNote;
    }

    public function setSellerNote(?string $sellerNote): self
    {
        $this->sellerNote = $sellerNote;

        return $this;
    }

    public function getCancelledBy(): ?string
    {
        return $this->cancelledBy;
    }

    public function setCancelledBy(?string $cancelledBy): self
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    public function getDriverPhone2(): ?string
    {
        return $this->driverPhone2;
    }

    public function setDriverPhone2(?string $driverPhone2): self
    {
        $this->driverPhone2 = $driverPhone2;

        return $this;
    }

    public function getDriverPhone3(): ?string
    {
        return $this->driverPhone3;
    }

    public function setDriverPhone3(?string $driverPhone3): self
    {
        $this->driverPhone3 = $driverPhone3;

        return $this;
    }

    public function getTotalDistance(): ?string
    {
        return $this->totalDistance;
    }

    public function setTotalDistance(?string $totalDistance): self
    {
        $this->totalDistance = $totalDistance;

        return $this;
    }

    public function getPickupEta(): ?string
    {
        return $this->pickupEta;
    }

    public function setPickupEta(?string $pickupEta): self
    {
        $this->pickupEta = $pickupEta;

        return $this;
    }

    public function getDeliveryEta(): ?string
    {
        return $this->deliveryEta;
    }

    public function setDeliveryEta(?string $deliveryEta): self
    {
        $this->deliveryEta = $deliveryEta;

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
        $this->createdAt = new Datetime('now');

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
        $this->updatedAt = new Datetime('now');

        return $this;
    }
}
