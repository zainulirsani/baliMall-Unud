<?php

namespace App\Entity;

use App\Validator\Constraints\DuplicateSlug;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product_category", indexes={@ORM\Index(name="product_category_parent_id_idx", columns={"parent_id"})})
 * @ORM\Entity(repositoryClass="App\Repository\ProductCategoryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="slug", message="global.slug_taken")
 * @DuplicateSlug()
 */
class ProductCategory extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", options={"default": 0})
     */
    private $parentId;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=200, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     * @Assert\Length(max=200)
     */
    private $heading;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $desktopImage;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $mobileImage;

    /**
     * @ORM\Column(type="boolean")
     */
    private $status;

    /**
     * @ORM\Column(type="boolean")
     */
    private $featured;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $dirSlug;

    /**
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $withTax;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $className;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    private $slugCheck;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $fee;

    public function __construct()
    {
        $this->parentId = 0;
        $this->status = false;
        $this->featured = false;
        $this->sort = 0;
        $this->withTax = false;
        $this->slugCheck = false;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function setParentId(int $parentId): void
    {
        $this->parentId = $parentId;
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

    public function getHeading()
    {
        return $this->heading;
    }

    public function setHeading(string $heading): void
    {
        $this->heading = $heading;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDesktopImage()
    {
        return $this->desktopImage;
    }

    public function setDesktopImage(?string $desktopImage): void
    {
        $this->desktopImage = $desktopImage;
    }

    public function getMobileImage()
    {
        return $this->mobileImage;
    }

    public function setMobileImage(?string $mobileImage): void
    {
        $this->mobileImage = $mobileImage;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getDirSlug()
    {
        return $this->dirSlug;
    }

    public function setDirSlug($dirSlug): void
    {
        $this->dirSlug = $dirSlug;
    }

    public function getWithTax(): bool
    {
        return $this->withTax;
    }

    public function setWithTax(bool $withTax): void
    {
        $this->withTax = $withTax;
    }

    public function getClassName(): bool
    {
        return $this->className;
    }

    public function setClassName(?string $className): void
    {
        $this->className = $className;
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

    public function getSlugCheck(): bool
    {
        return $this->slugCheck;
    }

    public function setSlugCheck(bool $slugCheck): void
    {
        $this->slugCheck = $slugCheck;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(?float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getData(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'heading' => $this->getHeading(),
            'description' => $this->getDescription(),
            'status' => $this->getStatus(),
            'featured' => $this->getFeatured(),
            'fee' => $this->getFee(),
            'parent_id' => $this->getParentId() === 0 ? null : $this->getParentId(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt() ? $this->getUpdatedAt()->format('Y-m-d H:i:s') : null
        ];
    }

}
