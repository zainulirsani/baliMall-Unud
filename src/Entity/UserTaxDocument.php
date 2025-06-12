<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="user_tax_document")
 * @ORM\Entity(repositoryClass="App\Repository\UserTaxDocumentRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class UserTaxDocument extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Tax Document <==> One User.
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="taxDocuments", fetch="EXTRA_LAZY")
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
     * @Assert\Length(max=255)
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max=100)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=20)
     * @Assert\Type(type="numeric", message="user.phone_numeric")
     */
    private $phone;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $image;

    /**
     * @ORM\Column(type="boolean")
     */
    private $sameAsProfile;

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
        $this->sameAsProfile = false;
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

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }

    public function getEmail()
    {
        return ($this->getSameAsProfile() && $this->getUser() instanceof User) ? $this->getUser()->getEmailCanonical() : $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone()
    {
        return ($this->getSameAsProfile()
            && $this->getUser() instanceof User
            && !empty($this->getUser()->getPhoneNumber())) ? $this->getUser()->getPhoneNumber() : $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }

    public function getSameAsProfile(): bool
    {
        return $this->sameAsProfile;
    }

    public function setSameAsProfile(bool $sameAsProfile): void
    {
        $this->sameAsProfile = $sameAsProfile;
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
