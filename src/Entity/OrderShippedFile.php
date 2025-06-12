<?php

namespace App\Entity;

use App\Repository\OrderShippedFileRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity(repositoryClass=OrderShippedFileRepository::class)
 */
class OrderShippedFile
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Order", inversedBy="orderShippedFiles", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $order;

    /**
     * @ORM\Column(type="text")
     */
    private $filepath;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?order
    {
        return $this->order;
    }

    public function setOrder(?order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): self
    {
        $this->filepath = $filepath;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(): self
    {
        $this->created_at = new DateTime();;

        return $this;
    }
}
