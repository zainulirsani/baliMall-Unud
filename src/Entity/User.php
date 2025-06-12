<?php

namespace App\Entity;

use App\Utility\GoogleMailHandler;
use App\Validator\Constraints\ValidDobDate;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="username", message="user.username_taken")
 */
class User extends BaseEntity implements UserInterface, \Serializable
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
     * @Assert\Length(min=3, max=100)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max=100)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\Length(max=100)
     */
    private $emailCanonical;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(min=6, max=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $dirSlug;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $role;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $activationCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $forgotPasswordCode;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $lastName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotBlank(groups={"b2g", "b2b"})
     * @Assert\Length(max=20)
     * @Assert\Type(type="numeric", message="user.phone_numeric")
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     * @Assert\NotBlank(allowNull=true)
     * @Assert\Choice({"male", "female", "others"})
     */
    private $gender;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $photoProfile;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $bannerProfile;

    /**
     * @ORM\Column(type="boolean")
     */
    private $newsletter;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @ValidDobDate()
     */
    private $dob;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @Assert\Length(max=40)
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"b2g"})
     * @Assert\Length(max=255, groups={"b2g"})
     */
    private $nip;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255, groups={"b2g"})
     */
    private $ppName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255, groups={"b2g"})
     */
    private $ppkName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"b2b"})
     * @Assert\Length(max=255, groups={"b2b"})
     */
    private $nik;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"b2b"})
     * @Assert\Length(max=255, groups={"b2b"})
     */
    private $companyPhone;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank(groups={"b2b"})
     * @Assert\Length(max=255, groups={"b2b"})
     */
    private $companyRole;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $npwp;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $npwpFile;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppLpseId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppEmployeeId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppGroups;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppKLDI;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppWorkUnit;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lkppTokenExpiration;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $lkppLoginStatus;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\NotBlank(allowNull=true)
     */
    private $tnc;

    /**
     * One User <==> Many Address.
     * @ORM\OneToMany(targetEntity="App\Entity\UserAddress", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $addresses;

    /**
     * One User <==> Many Tax Document.
     * @ORM\OneToMany(targetEntity="App\Entity\UserTaxDocument", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $taxDocuments;

    /**
     * One User <==> Many Store.
     * @ORM\OneToMany(targetEntity="App\Entity\Store", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $stores;

    /**
     * One User <==> Many Product Reviews.
     * @ORM\OneToMany(targetEntity="App\Entity\ProductReview", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $productReviews;

    /**
     * One User <==> Many Order (as buyer).
     * @ORM\OneToMany(targetEntity="App\Entity\Order", mappedBy="buyer", fetch="EXTRA_LAZY")
     */
    private $asBuyer;

    /**
     * One User <==> One Cart.
     * @ORM\OneToOne(targetEntity="App\Entity\Cart", mappedBy="user", fetch="EXTRA_LAZY")
     */
    private $cart;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $ktpFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $suratIjinFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $dokumenFile;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $lkppJwtToken;

    /**
     * @ORM\OneToMany(targetEntity=Operator::class, mappedBy="owner")
     */
    private $operators;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lkppRole;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lkppInstanceId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lkppInstanceName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lkppWorkunitId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lkppWorkunitName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $balimallToken;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adminMerchantBranchProvince;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $user_signature;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $user_stamp;

    /**
     * @ORM\OneToMany(targetEntity=UserPicDocument::class, mappedBy="user")
     */
    private $userPicDocuments;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $npwp_address;

    /**
     * @ORM\OneToMany(targetEntity=UserPpkTreasurer::class, mappedBy="user")
     */
    private $userPpkTreasurers;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subRole;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $subRoleTypeAccount;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $secureRandomCode;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $sendEmailAccess;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isUserTesting;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $NpwpName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vaBni;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $digitSatker;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $satkerId;

    /**
     * @ORM\OneToMany(targetEntity=Satker::class, mappedBy="user")
     */
    private $satkers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\DocumentApproval", mappedBy="approved_by")
     */
    private Collection $documentApprovals;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Bni", mappedBy="user")
     */
    private $bnis;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->taxDocuments = new ArrayCollection();
        $this->stores = new ArrayCollection();
        $this->productReviews = new ArrayCollection();
        $this->asBuyer = new ArrayCollection();
        $this->isActive = false;
        $this->isDeleted = false;
        $this->newsletter = false;
        $this->operators = new ArrayCollection();
        $this->userPicDocuments = new ArrayCollection();
        $this->userPpkTreasurers = new ArrayCollection();
        $this->bnis = new ArrayCollection();
        $this->satkers = new ArrayCollection();
        $this->documentApprovals = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = GoogleMailHandler::validate($email);
    }

    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    public function setEmailCanonical(string $emailCanonical): void
    {
        $this->emailCanonical = $emailCanonical;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getDirSlug()
    {
        return $this->dirSlug;
    }

    public function setDirSlug($dirSlug): void
    {
        $this->dirSlug = $dirSlug;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getActivationCode()
    {
        return $this->activationCode;
    }

    public function setActivationCode(?string $activationCode): void
    {
        $this->activationCode = $activationCode;
    }

    public function getForgotPasswordCode()
    {
        return $this->forgotPasswordCode;
    }

    public function setForgotPasswordCode(?string $forgotPasswordCode): void
    {
        $this->forgotPasswordCode = $forgotPasswordCode;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getIsDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getGender()
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function getPhotoProfile()
    {
        return $this->photoProfile;
    }

    public function setPhotoProfile(?string $photoProfile): void
    {
        $this->photoProfile = $photoProfile;
    }

    public function getBannerProfile()
    {
        return $this->bannerProfile;
    }

    public function setBannerProfile(?string $bannerProfile): void
    {
        $this->bannerProfile = $bannerProfile;
    }

    public function getNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function setNewsletter(bool $newsletter): void
    {
        $this->newsletter = $newsletter;
    }

    public function getDob()
    {
        return $this->dob;
    }

    public function setDob($dob): void
    {
        $this->dob = $dob;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress(): void
    {
        $this->ipAddress = $_SERVER['REMOTE_ADDR'];
    }

    public function getNip()
    {
        return $this->nip;
    }

    public function setNip(?string $nip): void
    {
        $this->nip = $nip;
    }

    public function getPpName()
    {
        return $this->ppName;
    }

    public function setPpName(?string $ppName): void
    {
        $this->ppName = $ppName;
    }

    public function getPpkName()
    {
        return $this->ppkName;
    }

    public function setPpkName(?string $ppkName): void
    {
        $this->ppkName = $ppkName;
    }

    public function getNik()
    {
        return $this->nik;
    }

    public function setNik(?string $nik): void
    {
        $this->nik = $nik;
    }

    public function getCompanyPhone()
    {
        return $this->companyPhone;
    }

    public function setCompanyPhone(?string $companyPhone): void
    {
        $this->companyPhone = $companyPhone;
    }

    public function getCompanyRole()
    {
        return $this->companyRole;
    }

    public function setCompanyRole(?string $companyRole): void
    {
        $this->companyRole = $companyRole;
    }

    public function getNpwp()
    {
        return $this->npwp;
    }

    public function setNpwp(?string $npwp): void
    {
        $this->npwp = $npwp;
    }

    public function getNpwpFile()
    {
        return $this->npwpFile;
    }

    public function setNpwpFile(?string $npwpFile): void
    {
        $this->npwpFile = $npwpFile;
    }

    public function getKtpFile()
    {
        return $this->ktpFile;
    }

    public function setKtpFile(?string $ktpFile): void
    {
        $this->ktpFile = $ktpFile;
    }

    public function getLkppLpseId()
    {
        return $this->lkppLpseId;
    }

    public function setLkppLpseId(?string $lkppLpseId): void
    {
        $this->lkppLpseId = $lkppLpseId;
    }

    public function getLkppEmployeeId()
    {
        return $this->lkppEmployeeId;
    }

    public function setLkppEmployeeId(?string $lkppEmployeeId): void
    {
        $this->lkppEmployeeId = $lkppEmployeeId;
    }

    public function getLkppGroups()
    {
        return $this->lkppGroups;
    }

    public function setLkppGroups(?string $lkppGroups): void
    {
        $this->lkppGroups = $lkppGroups;
    }

    public function getLkppKLDI()
    {
        return $this->lkppKLDI;
    }

    public function setLkppKLDI(?string $lkppKLDI): void
    {
        $this->lkppKLDI = $lkppKLDI;
    }

    public function getLkppWorkUnit()
    {
        return $this->lkppWorkUnit;
    }

    public function setLkppWorkUnit(?string $lkppWorkUnit): void
    {
        $this->lkppWorkUnit = $lkppWorkUnit;
    }

    public function getLkppToken()
    {
        return $this->lkppToken;
    }

    public function setLkppToken(?string $lkppToken): void
    {
        $this->lkppToken = $lkppToken;
    }

    public function getLkppTokenExpiration()
    {
        return $this->lkppTokenExpiration;
    }

    public function setLkppTokenExpiration(): void
    {
        $this->lkppTokenExpiration = (new DateTime('now'))->modify('+2 hours');
    }

    public function updateLkppTokenExpiration(): void
    {
        $this->lkppTokenExpiration = null;
    }

    public function getLkppLoginStatus()
    {
        return $this->lkppLoginStatus;
    }

    public function setLkppLoginStatus(?string $lkppLoginStatus): void
    {
        $this->lkppLoginStatus = $lkppLoginStatus;
    }

    public function nullifyLkppUserToken(): void
    {
        $this->lkppToken = null;
        $this->lkppTokenExpiration = null;
        $this->lkppLoginStatus = null;
    }

    public function getTnc()
    {
        return $this->tnc;
    }

    public function setTnc($tnc): void
    {
        $this->tnc = $tnc;
    }

    /**
     * @return Collection|UserAddress[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @return Collection|UserTaxDocument[]
     */
    public function getTaxDocuments()
    {
        return $this->taxDocuments;
    }

    /**
     * @return Collection|Store[]
     */
    public function getStores()
    {
        return $this->stores;
    }

    /**
     * @return Collection|Store[]
     */
    public function getStoreValues()
    {
        return $this->stores->getValues()[0] ?? $this->stores->getValues();
    }

    /**
     * @return Collection|ProductReview[]
     */
    public function getProductReviews()
    {
        return $this->productReviews;
    }

    /**
     * @return Collection|Order[]
     */
    public function getAsBuyer()
    {
        return $this->asBuyer;
    }

    public function getCart()
    {
        return $this->cart;
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

    public function isEnabled(): bool
    {
        return $this->isActive;
    }

    public function isRemoved(): bool
    {
        return $this->isDeleted;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function serialize(): string
    {
        return serialize([
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->role,
            $this->isActive,
            $this->firstName,
            $this->lastName
        ]);
    }

    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->username,
            $this->email,
            $this->password,
            $this->role,
            $this->isActive,
            $this->firstName,
            $this->lastName
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function getFirstAddress()
    {
        $addresses = $this->getAddresses();

        return (count($addresses) > 0) ? $addresses[0] : [];
    }

    public function getSuratIjinFile()
    {
        return !empty($this->suratIjinFile) ? array_unique(json_decode($this->suratIjinFile, true)) : [];
    }

    public function setSuratIjinFile(array $suratIjinFile = []): void
    {
        $this->suratIjinFile = json_encode($suratIjinFile);
    }

    public function getDokumenFile()
    {
        return !empty($this->dokumenFile) ? array_unique(json_decode($this->dokumenFile, true)) : [];
    }

    public function setDokumenFile(array $dokumenFile = []): void
    {
        $this->dokumenFile = json_encode($dokumenFile);
    }

    public function getLkppJwtToken(): ?string
    {
        return $this->lkppJwtToken;
    }

    public function setLkppJwtToken(?string $lkppJwtToken): self
    {
        $this->lkppJwtToken = $lkppJwtToken;

        return $this;
    }

    /**
     * @return Collection|Operator[]
     */
    public function getOperators(): Collection
    {
        return $this->operators;
    }

    public function addOperator(Operator $operator): self
    {
        if (!$this->operators->contains($operator)) {
            $this->operators[] = $operator;
            $operator->setOwner($this);
        }

        return $this;
    }

    public function removeOperator(Operator $operator): self
    {
        if ($this->operators->removeElement($operator)) {
            // set the owning side to null (unless already changed)
            if ($operator->getOwner() === $this) {
                $operator->setOwner(null);
            }
        }

        return $this;
    }

    public function getLkppRole(): ?string
    {
        return $this->lkppRole;
    }

    public function setLkppRole(string $lkppRole): self
    {
        $this->lkppRole = $lkppRole;

        return $this;
    }

    public function getLkppInstanceId(): ?string
    {
        return $this->lkppInstanceId;
    }

    public function setLkppInstanceId(?string $lkppInstanceId): self
    {
        $this->lkppInstanceId = $lkppInstanceId;

        return $this;
    }

    public function getLkppInstanceName(): ?string
    {
        return $this->lkppInstanceName;
    }

    public function setLkppInstanceName(?string $lkppInstanceName): self
    {
        $this->lkppInstanceName = $lkppInstanceName;

        return $this;
    }

    public function getLkppWorkunitId(): ?string
    {
        return $this->lkppWorkunitId;
    }

    public function setLkppWorkunitId(?string $lkppWorkunitId): self
    {
        $this->lkppWorkunitId = $lkppWorkunitId;

        return $this;
    }

    public function getLkppWorkunitName(): ?string
    {
        return $this->lkppWorkunitName;
    }

    public function setLkppWorkunitName(?string $lkppWorkunitName): self
    {
        $this->lkppWorkunitName = $lkppWorkunitName;

        return $this;
    }

    public function getBalimallToken(): ?string
    {
        return $this->balimallToken;
    }

    public function setBalimallToken(?string $balimallToken): self
    {
        $this->balimallToken = $balimallToken;

        return $this;
    }

    public function getAdminMerchantBranchProvince(): ?string
    {
        return $this->adminMerchantBranchProvince;
    }

    public function setAdminMerchantBranchProvince(?string $adminMerchantBranchProvince): self
    {
        $this->adminMerchantBranchProvince = $adminMerchantBranchProvince;

        return $this;
    }

    public function getUserSignature(): ?string
    {
        return $this->user_signature;
    }

    public function setUserSignature(?string $user_signature): self
    {
        $this->user_signature = $user_signature;

        return $this;
    }

    public function getUserStamp(): ?string
    {
        return $this->user_stamp;
    }

    public function setUserStamp(?string $user_stamp): self
    {
        $this->user_stamp = $user_stamp;

        return $this;
    }

    /**
     * @return Collection|UserPicDocument[]
     */
    public function getUserPicDocuments(): Collection
    {
        return $this->userPicDocuments;
    }

    public function getNpwpAddress(): ?string
    {
        return $this->npwp_address;
    }

    public function setNpwpAddress(?string $npwp_address): self
    {
        $this->npwp_address = $npwp_address;

        return $this;
    }

    /**
     * @return Collection|UserPpkTreasurer[]
     */
    public function getUserPpkTreasurers(): Collection
    {
        return $this->userPpkTreasurers;
    }

    public function addUserPpkTreasurer(UserPpkTreasurer $userPpkTreasurer): self
    {
        if (!$this->userPpkTreasurers->contains($userPpkTreasurer)) {
            $this->userPpkTreasurers[] = $userPpkTreasurer;
            $userPpkTreasurer->setUser($this);
        }

        return $this;
    }

    public function removeUserPpkTreasurer(UserPpkTreasurer $userPpkTreasurer): self
    {
        if ($this->userPpkTreasurers->removeElement($userPpkTreasurer)) {
            // set the owning side to null (unless already changed)
            if ($userPpkTreasurer->getUser() === $this) {
                $userPpkTreasurer->setUser(null);
            }
        }

        return $this;
    }

    public function getSubRole(): ?string
    {
        return $this->subRole;
    }

    public function setSubRole(?string $subRole): self
    {
        $this->subRole = $subRole;

        return $this;
    }

    public function getSubRoleTypeAccount(): ?string
    {
        return $this->subRoleTypeAccount;
    }

    public function setSubRoleTypeAccount(?string $subRoleTypeAccount): self
    {
        $this->subRoleTypeAccount = $subRoleTypeAccount;

        return $this;
    }

    public function getSecureRandomCode(): ?string
    {
        return $this->secureRandomCode;
    }

    public function setSecureRandomCode(?string $secureRandomCode): self
    {
        $this->secureRandomCode = $secureRandomCode;

        return $this;
    }

    public function getSendEmailAccess(): ?bool
    {
        return $this->sendEmailAccess;
    }

    public function setSendEmailAccess(?bool $sendEmailAccess): self
    {
        $this->sendEmailAccess = $sendEmailAccess;
        return $this;
    }

    public function getIsUserTesting(): ?bool
    {
        return $this->isUserTesting;
    }

    public function setIsUserTesting(?bool $isUserTesting): self
    {
        $this->isUserTesting = $isUserTesting;

        return $this;
    }

    public function getNpwpName(): ?string
    {
        return $this->NpwpName;
    }

    public function setNpwpName(?string $NpwpName): self
    {
        $this->NpwpName = $NpwpName;

        return $this;
    }

    public function getVaBni(): ?string
    {
        return $this->vaBni;
    }

    public function setVaBni(?string $vaBni): self
    {
        $this->vaBni = $vaBni;

        return $this;
    }

    public function getDigitSatker(): ?string
    {
        return $this->digitSatker;
    }

    public function setDigitSatker(?string $digitSatker): self
    {
        $this->digitSatker = $digitSatker;

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

    /**
     * @return Collection|Satker[]
     */
    public function getSatkers(): Collection
    {
        return $this->satkers;
    }

    public function addSatker(Satker $satker): self
    {
        if (!$this->satkers->contains($satker)) {
            $this->satkers[] = $satker;
            $satker->setUser($this);
        }

        return $this;
    }

    public function removeSatker(Satker $satker): self
    {
        if ($this->satkers->removeElement($satker)) {
            // set the owning side to null (unless already changed)
            if ($satker->getUser() === $this) {
                $satker->setUser(null);
            }
        }

        return $this;
    }

    public function getUserBpdBinding(): ?UserBpdBinding
    {
        return $this->userBpdBinding;
    }

    public function setUserBpdBinding(?UserBpdBinding $userBpdBinding): self
    {
        $this->userBpdBinding = $userBpdBinding;

        return $this;
    }

    public function getDocumentApprovals(): Collection {
        return $this->documentApprovals;
    }

    public function getBnis(): Collection
    {
        return $this->bnis;
    }

    public function addBni(Bni $bni): self
    {
        if (!$this->bnis->contains($bni)) {
            $this->bnis[] = $bni;
            $bni->setUser($this);
        }

        return $this;
    }

    public function removeBni(Bni $bni): self
    {
        if ($this->bnis->removeElement($bni)) {
            // set the owning side to null
            if ($bni->getUser() === $this) {
                $bni->setUser(null);
            }
        }

        return $this;
    }

}
