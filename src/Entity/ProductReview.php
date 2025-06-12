<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product_review")
 * @ORM\Entity(repositoryClass="App\Repository\ProductReviewRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProductReview extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Reviews <==> One Order.
     * @ORM\ManyToOne(targetEntity="App\Entity\Order", inversedBy="productReviews", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * Many Reviews <==> One Product.
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="reviews", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $product;

    /**
     * Many Reviews <==> One User.
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="productReviews", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Length(max=5000)
     */
    private $review;

    /**
     * @ORM\Column(type="smallint", options={"default": 0})
     * @Assert\NotBlank()
     * @Assert\GreaterThan(0)
     * @Assert\LessThanOrEqual(5)
     */
    private $rating;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "draft"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"publish", "draft", "deleted"}))
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $attachment;

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
        $this->rating = 0;
        $this->status = 'draft';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getReview()
    {
        return $this->review;
    }

    public function setReview($review): void
    {
        $this->review = $review;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating($rating): void
    {
        $this->rating = $rating;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getAttachment(): string
    {
        return $this->attachment;
    }

    public function setAttachment(string $attachment): void
    {
        $this->attachment = $attachment;
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
