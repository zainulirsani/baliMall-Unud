<?php

namespace App\Entity;

use App\Helper\StaticHelper;
use App\Validator\Constraints\ReservedNames;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="store")
 * @ORM\Entity(repositoryClass="App\Repository\StoreRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="slug", message="global.slug_taken")
 * @ReservedNames()
 */
class Store extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * One User <==> Many Store.
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="stores", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Length(max=200)
     */
    private $brand;

    /**
     * @return mixed
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param mixed $brand
     */
    public function setBrand($brand): void
    {
        $this->brand = $brand;
    }

    /**
     * @ORM\Column(type="string", length=200, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $color;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $theme;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified;

    /**
     * @ORM\Column(type="boolean", options={"default": null}, nullable=true)
     */
    private $isPKP;

    /**
     * @ORM\Column(type="smallint", options={"default": 0}, nullable=true)
     */
    private $tnc;

    /**
     * @return mixed
     */
    public function getTnc()
    {
        return $this->tnc;
    }

    /**
     * @param mixed $tnc
     */
    public function setTnc($tnc): void
    {
        $this->tnc = $tnc;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $deliveryCouriers;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $address;

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
     * @Assert\NotBlank()
     * @Assert\Length(min=5, max=5)
     */
    private $postCode;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=100)
     */
    private $city;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
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
     * @Assert\NotBlank()
     * @Assert\Length(max=100)
     */
    private $province;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     * @Assert\NotBlank()
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
     * @ORM\Column(type="text", nullable=true)
     */
    private $previousValues;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $previousChanges;

    /**
     * One Product <==> Many Store.
     * @ORM\OneToMany(targetEntity="App\Entity\Product", mappedBy="store", fetch="EXTRA_LAZY")
     */
    private $products;

    /**
     * One Store <==> Many Order (as seller).
     * @ORM\OneToMany(targetEntity="App\Entity\Order", mappedBy="seller", fetch="EXTRA_LAZY")
     */
    private $asSeller;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2, nullable=true)
     */
    private $modalUsaha;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $totalManpower;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $rekeningName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $bankName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $nomorRekening;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $rekeningFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $sppkpFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice({"PERSEORANGAN","BADAN_USAHA"})
     */
    private $typeOfBusiness;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice({"USAHA_MIKRO","USAHA_KECIL","USAHA_MENENGAH","USAHA_BESAR"})
     */
    private $businessCriteria;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     * @Assert\Choice({"NEW_MERCHANT","ACTIVE", "UPDATE", "DRAFT", "PENDING", "INACTIVE","VERIFIED"}))
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $registeredNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     * @Assert\Choice({"PEMILIK","DIREKTUR"}))
     */
    private $position;


    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $fileHash;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $statusLog;

    /**
     * @return mixed
     */
    public function getStatusLog()
    {
        return json_decode($this->statusLog, true);
    }

    /**
     * @param mixed $statusLog
     */
    public function setStatusLog($status): void
    {
        $previousStatus = $this->getStatusLog();
        $statusLog = [
            'time' => date('d-m-Y H.i'),
            'status' => $status
        ];

        if (!empty($previousStatus)) {
            $previousStatus[] = $statusLog;
            $this->statusLog = json_encode($previousStatus);
        }else {
            $this->statusLog = json_encode([$statusLog]);
        }
    }


    /**
     * @return mixed
     */
    public function getFileHash()
    {
        return $this->fileHash;
    }

    /**
     * @param mixed $fileHash
     */
    public function setFileHash($fileHash): void
    {
        $this->fileHash = $fileHash;
    }

    /**
     * @return mixed
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param mixed $position
     */
    public function setPosition($position): void
    {
        $this->position = $position;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $productCategories;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $umkm_category;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $shopId;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isUsedErzap;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $idTayang;

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param mixed $note
     */
    public function setNote($note): void
    {
        $this->note = $note;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getBusinessCriteria()
    {
        return $this->businessCriteria;
    }

    /**
     * @param mixed $businessCriteria
     */
    public function setBusinessCriteria($businessCriteria): void
    {
        $this->businessCriteria = $businessCriteria;
    }

    /**
     * @return mixed
     */
    public function getRegisteredNumber()
    {
        return $this->registeredNumber;
    }

    /**
     * @param mixed $registeredNumber
     */
    public function setRegisteredNumber($registeredNumber): void
    {
        $this->registeredNumber = $registeredNumber;
    }

    /**
     * @return mixed
     */
    public function getTypeOfBusiness()
    {
        return $this->typeOfBusiness;
    }

    /**
     * @param mixed $typeOfBusiness
     */
    public function setTypeOfBusiness(string $typeOfBusiness): void
    {
        $this->typeOfBusiness = $typeOfBusiness;
    }

    public function __construct()
    {
        $this->isActive = false;
        $this->isVerified = false;
//        $this->isPKP = false;
        $this->products = new ArrayCollection();
        $this->asSeller = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setTheme(string $theme): void
    {
        $this->theme = $theme;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): void
    {
        $this->isVerified = $isVerified;
    }

    public function getIsPKP()
    {
        return $this->isPKP;
    }

    public function setIsPKP(bool $isPKP): void
    {
        $this->isPKP = $isPKP;
    }

    public function getDeliveryCouriers()
    {
        return !empty($this->deliveryCouriers) ? array_unique(json_decode($this->deliveryCouriers, true)) : [];
    }

    public function setDeliveryCouriers(array $deliveryCouriers = []): void
    {
        $this->deliveryCouriers = json_encode($deliveryCouriers);
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
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

    public function setAddressLat(float $addressLat): void
    {
        $this->addressLat = $addressLat;
    }

    public function getAddressLng()
    {
        return $this->addressLng;
    }

    public function setAddressLng(float $addressLng): void
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

    public function getPreviousValues()
    {
        return json_decode($this->previousValues, true);
    }

    public function setPreviousValues(array $previousValues = []): void
    {
        $this->previousValues = json_encode($previousValues);
    }

    public function getPreviousChanges()
    {
        return json_decode($this->previousChanges, true);
    }

    public function setPreviousChanges(array $previousChanges = []): void
    {
        $this->previousChanges = json_encode($previousChanges);
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return Collection|Order[]
     */
    public function getAsSeller()
    {
        return $this->asSeller;
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

    public function originalData(): array
    {
        return [
            's_name' => $this->getName(),
            's_brand' => $this->getBrand(),
            's_description' => $this->getDescription(),
            's_address' => $this->getAddress(),
            's_postCode' => $this->getPostCode(),
            's_city' => $this->getCity(),
            's_cityId' => (int) $this->getCityId(),
            's_district' => $this->getDistrict(),
            's_districtId' => (int) $this->getDistrictId(),
            's_province' => $this->getProvince(),
            's_provinceId' => (int) $this->getProvinceId(),
            's_country' => $this->getCountry(),
            's_countryId' => (int) $this->getCountryId(),
            's_isActive' => $this->getIsActive(),
            's_isVerified' => $this->getIsVerified(),
            's_deliveryCouriers' => $this->getDeliveryCouriers(),
            's_typeOfBusiness' => $this->getTypeOfBusiness(),
            's_modalUsaha' => (float) $this->getModalUsaha(),
            's_totalManpower' => $this->getTotalManpower(),
            's_bankName' => $this->getBankName(),
            's_nomorRekening' => $this->getNomorRekening(),
            's_rekeningFile' => $this->getRekeningFile(),
            's_sppkpFile' => $this->getSppkpFile(),
            's_registeredNumber' => $this->getRegisteredNumber(),
            's_businessCriteria' => $this->getBusinessCriteria(),
            's_rekeningName' => $this->getRekeningName(),
            's_position' => $this->getPosition(),
            's_isPKP' => $this->getIsPKP() ? 'pkp' : 'non-pkp',
            's_productCategories' => $this->getProductCategories(),

            'u_npwpFile' => $this->getUser()->getNpwpFile(),
            'u_npwpName' => $this->getUser()->getNpwpName(),
            'u_npwp' => $this->getUser()->getNpwp(),
            'u_ktpFile' => $this->getUser()->getKtpFile(),
            'u_suratIjinFile' => $this->getUser()->getSuratIjinFile(),
            'u_dokumenFile' => $this->getUser()->getDokumenFile(),
            'u_nik' => $this->getUser()->getNik(),
            'u_dob' => $this->getUser()->getDob() ? $this->getUser()->getDob()->format('Y-m-d') : '',
            'u_gender' => $this->getUser()->getGender(),
            'u_fullName' => $this->getUser()->getFirstName().' '.$this->getUser()->getLastName(),
            'u_email' => $this->getUser()->getEmail(),
            'u_phoneNumber' => $this->getUser()->getPhoneNumber(),

//            'u_photoProfile' => $this->getUser()->getPhotoProfile(),
//            'u_bannerProfile' => $this->getUser()->getBannerProfile(),
        ];
    }

    public function setModalUsaha(float $modalUsaha): void
    {
        $this->modalUsaha = $modalUsaha;
    }

    public function getModalUsaha()
    {
        return $this->modalUsaha;
    }

    public function setTotalManpower(string $totalManpower): void
    {
        $this->totalManpower = $totalManpower;
    }

    public function getTotalManpower()
    {
        return $this->totalManpower;
    }

    public function setRekeningName(string $rekeningName): void
    {
        $this->rekeningName = $rekeningName;
    }

    public function getRekeningName()
    {
        return $this->rekeningName;
    }

    public function setBankName(string $bankName): void
    {
        $this->bankName = $bankName;
    }

    public function getBankName()
    {
        return $this->bankName;
    }

    public function setNomorRekening(string $nomorRekening): void
    {
        $this->nomorRekening = $nomorRekening;
    }

    public function getNomorRekening()
    {
        return $this->nomorRekening;
    }

    public function getRekeningFile()
    {
        return $this->rekeningFile;
    }

    public function setRekeningFile(?string $rekeningFile): void
    {
        $this->rekeningFile = $rekeningFile;
    }

    public function getSppkpFile()
    {
        return !empty($this->sppkpFile) ? array_unique(json_decode($this->sppkpFile, true)) : [];
    }

    public function setSppkpFile(array $sppkpFile = []): void
    {
        $this->sppkpFile = json_encode($sppkpFile);
    }

    public function getData(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'description' => $this->getDescription(),
            'address' => $this->getAddress(),
            'address_lat' => $this->getAddressLat(),
            'address_lng' => $this->getAddressLng(),
            'post_code' => $this->getPostCode(),
            'city' => $this->getCity(),
            'city_id' => $this->getCityId(),
            'district' => $this->getDistrict(),
            'district_id' => $this->getDistrictId(),
            'province' => $this->getProvince(),
            'province_id' => $this->getProvinceId(),
            'country' => $this->getCountry(),
            'country_id' => $this->getCountryId(),
            'is_verified' => $this->getIsVerified() ? 1 : 0,
            'delivery_couriers' => $this->getDeliveryCouriers(),
            'is_pkp' => $this->getIsPKP() ? 1 : 0,
            'modal_usaha' => $this->getModalUsaha(),
            'total_manpower' => $this->getTotalManpower(),
            'rekening_name' => $this->getRekeningName(),
            'bank_name' => $this->getBankName(),
            'type_of_business' => $this->getTypeOfBusiness(),
            'status' => $this->getStatus(),
//            'image' => $this->getUser()->getPhotoProfile() ? $this->getUser()->getPhotoProfile() : '',
            'brand' => $this->getBrand(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt() ? $this->getUpdatedAt()->format('Y-m-d H:i:s') : '',
        ];
    }

    public function getProductCategories(): ?array
    {
        return !empty($this->productCategories) ? array_unique(json_decode($this->productCategories, true)) : [];
    }

    public function setProductCategories(?array $productCategories): self
    {
        $this->productCategories = json_encode($productCategories);

        return $this;
    }

    public function getUmkmCategory(): ?string
    {
        return $this->umkm_category;
    }

    public function setUmkmCategory(?string $umkm_category): self
    {
        $this->umkm_category = $umkm_category;

        return $this;
    }

    public function getShopId(): ?string
    {
        return $this->shopId;
    }

    public function setShopId(?string $shopId): self
    {
        $this->shopId = $shopId;

        return $this;
    }

    public function getIsUsedErzap(): ?bool
    {
        return $this->isUsedErzap;
    }

    public function setIsUsedErzap(?bool $isUsedErzap): self
    {
        $this->isUsedErzap = $isUsedErzap;

        return $this;
    }

    public function getIdTayang(): ?string
    {
        return $this->idTayang;
    }

    public function setIdTayang(?string $idTayang): self
    {
        $this->idTayang = $idTayang;

        return $this;
    }
}
