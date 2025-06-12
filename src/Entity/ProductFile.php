<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="product_file")
 * @ORM\Entity(repositoryClass="App\Repository\ProductFileRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ProductFile extends BaseEntity
{
    /**
     * @ORM\Column(type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Many Files <==> One Product.
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="files", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $product;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\Length(max=100)
     */
    private $fileName;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "image"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"image", "video", "audio", "document"})
     */
    private $fileType;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\Length(max=20)
     */
    private $fileMimeType;

    /**
     * @ORM\Column(type="string", length=200)
     * @Assert\Length(max=200)
     */
    private $filePath;

    /**
     * @ORM\Column(type="string", length=20, options={"default": "draft"})
     * @Assert\Length(max=20)
     * @Assert\Choice({"publish", "draft", "deleted"}))
     */
    private $fileStatus;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDefault;

    /**
     * @ORM\Column(type="integer")
     */
    private $sort;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    public function __construct()
    {
        $this->fileType = 'image';
        $this->fileStatus = 'publish';
        $this->isDefault = false;
        $this->sort = 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getFileMimeType()
    {
        return $this->fileMimeType;
    }

    public function setFileMimeType(string $fileMimeType): void
    {
        $this->fileMimeType = $fileMimeType;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFileStatus(): string
    {
        return $this->fileStatus;
    }

    public function setFileStatus(string $fileStatus): void
    {
        $this->fileStatus = $fileStatus;
    }

    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
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
}
