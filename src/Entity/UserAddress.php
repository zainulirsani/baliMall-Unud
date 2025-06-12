<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="user_address")
 * @ORM\Entity(repositoryClass="App\Repository\UserAddressRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class UserAddress extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Address <==> One User.
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="addresses", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $title;

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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

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

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
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
