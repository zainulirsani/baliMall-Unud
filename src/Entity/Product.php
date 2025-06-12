<?php

namespace App\Entity;

use App\Validator\Constraints\ProductPriceCheck;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ProductPriceCheck()
 */
class Product extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Product <==> One Store.
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="products", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="store_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $store;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\NotBlank()
     * @Assert\Length(max=200)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $category;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Length(max=255)
     */
    private $keywords;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(0)
     */
    private $price;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=2)
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(0)
     */
    private $basePrice;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "draft"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"publish", "draft", "pending", "new_product", "product_updated", "deleted"}))
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "unit"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"unit", "pcs", "box", "package", "rim", "doos", "pack", "buah", "lusin", "set", "roll", "sloop", "dus", "bottle", "pair", "meter", "m2", "eksemplar", "galon","pail", "kg", "gram"}))
     */
    private $unit;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     */
    private $weight;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private $dirSlug;

    /**
     * @ORM\Column(type="boolean")
     */
    private $featured;

    /**
     * @ORM\Column(type="bigint")
     */
    private $viewCount;

    /**
     * @ORM\Column(type="bigint")
     */
    private $ratingCount;

    /**
     * @ORM\Column(type="bigint")
     */
    public $reviewTotal;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $previousValues;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $previousChanges;

    /**
     * One Product <==> Many Files.
     * @ORM\OneToMany(targetEntity="App\Entity\ProductFile", mappedBy="product", fetch="EXTRA_LAZY")
     */
    private $files;

    /**
     * One Product <==> Many Reviews.
     * @ORM\OneToMany(targetEntity="App\Entity\ProductReview", mappedBy="product", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"createdAt"="DESC"})
     */
    private $reviews;

    /**
     * One Product <==> Many Order Products.
     * @ORM\OneToMany(targetEntity="App\Entity\OrderProduct", mappedBy="product", fetch="EXTRA_LAZY")
     */
    private $orders;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $adminNote;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $is_pdn;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $productViewType;

     /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    private $idProductTayang;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->quantity = 0;
        $this->status = 'draft';
        $this->unit = 'unit';
        $this->featured = false;
        $this->viewCount= 0;
        $this->ratingCount= 0;
        $this->reviewTotal= 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): void
    {
        $this->store = $store;
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

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getNote()
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    public function getBasePrice()
    {
        return $this->basePrice;
    }

    public function setBasePrice(float $basePrice): void
    {
        $this->basePrice = $basePrice;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): void
    {
        $this->weight = $weight;
    }

    public function getDirSlug()
    {
        return $this->dirSlug;
    }

    public function setDirSlug(string $dirSlug): void
    {
        $this->dirSlug = $dirSlug;
    }

    public function getFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): void
    {
        $this->viewCount = $viewCount;
    }

    public function getRatingCount(): int
    {
        return $this->ratingCount;
    }

    public function setRatingCount(int $ratingCount): void
    {
        $this->ratingCount = $ratingCount;
    }

    public function getReviewTotal(): int
    {
        return $this->reviewTotal;
    }

    public function setReviewTotal(int $reviewTotal): void
    {
        $this->reviewTotal = $reviewTotal;
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
     * @return Collection|ProductFile[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return Collection|ProductReview[]
     */
    public function getReviews()
    {
        return $this->reviews;
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

    public function incrementViewCount(): void
    {
        $viewCount = $this->getViewCount() + 1;

        $this->setViewCount($viewCount);
    }

    public function sumRatingCount(int $rating): void
    {
        $ratingCount = $this->getRatingCount() + $rating;

        $this->setRatingCount($ratingCount);
    }

    public function incrementReviewTotal(): void
    {
        $reviewTotal = $this->getReviewTotal() + 1;

        $this->setReviewTotal($reviewTotal);
    }

    public function originalData(): array
    {
        $originalStoreId = 0;

        if (!empty($this->getStore())) {
            /** @var Store $originalStore */
            $originalStore = $this->getStore();
            $originalStoreId = (int) $originalStore->getId();
        }

        return [
            'p_name' => $this->getName(),
            'p_category' => $this->getCategory(),
            'p_keywords' => $this->getKeywords(),
            'p_description' => $this->getDescription(),
            'p_note' => $this->getNote(),
            'p_quantity' => $this->getQuantity(),
            'p_price' => (float) $this->getPrice(),
            'p_basePrice' => (float) $this->getBasePrice(),
            'p_weight' => (int) $this->getWeight(),
            'p_id' => (int) $this->getId(),
            's_id' => $originalStoreId,
            'p_dirSlug' => $this->getDirSlug(),
            'p_unit' => $this->getUnit(),
        ];
    }

    public function originalFiles(): array
    {
        $files = [];

        foreach ($this->getFiles() as $file) {
            $files[] = $file->getFilePath();
        }

        return $files;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): self
    {
        $this->adminNote = $adminNote;

        return $this;
    }

    public function getData(): array
    {
//        $images = [];
//
//        if (!empty($this->getFiles())) {
//            foreach ($this->getFiles() as $file) {
//                $images[] = $file->getFilePath();
//            }
//        }

        $result = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'category' => $this->getCategory(),
            'keyword' => $this->getKeywords(),
            'description' => $this->getDescription(),
            'note' => $this->getNote(),
            'quantity' => $this->getQuantity(),
            'price' => (float) $this->getPrice(),
            'base_price' => (float) $this->getBasePrice(),
            'status' => $this->getStatus(),
            'store_id' => $this->getStore()->getId(),
            'weight' => $this->getWeight(),
            'unit' => $this->getUnit(),
//            'images' => $images,
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt() ? $this->getUpdatedAt()->format('Y-m-d H:i:s') : null,
        ];

        return $result;
    }

    public function getIsPdn(): ?string
    {
        return $this->is_pdn;
    }

    public function setIsPdn(?string $is_pdn): self
    {
        $this->is_pdn = $is_pdn;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getProductViewType(): ?string
    {
        return $this->productViewType;
    }

    public function setProductViewType(?string $productViewType): self
    {
        $this->productViewType = $productViewType;

        return $this;
    }

    public function getIdProductTayang(): ?string
    {
        return $this->idProductTayang;
    }

    public function setIdProductTayang(?string $idProductTayang): self
    {
        $this->idProductTayang = $idProductTayang;

        return $this;
    }   
}
