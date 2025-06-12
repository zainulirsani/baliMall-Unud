<?php

namespace App\Entity;

use App\Repository\DocumentApprovalRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DocumentApprovalRepository::class)
 */
class DocumentApproval
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="documentApprovals")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", nullable=false)
     */
    private $order_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type_document;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="documentApprovals")
     * @ORM\JoinColumn(name="approved_by", referencedColumnName="id", nullable=false)
     */
    private $approved_by;

    /**
     * @ORM\Column(type="datetime")
     */
    private $approved_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?Order
    {
        return $this->order_id;
    }

    public function setOrderId(?Order $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getTypeDocument(): ?string
    {
        return $this->type_document;
    }

    public function setTypeDocument(string $type_document): self
    {
        $this->type_document = $type_document;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approved_by;
    }

    public function setApprovedBy(?User $approved_by): self
    {
        $this->approved_by = $approved_by;

        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approved_at;
    }

    public function setApprovedAt(\DateTimeInterface $approved_at): self
    {
        $this->approved_at = $approved_at;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(): self
    {
        $this->created_at = new DateTime();

        return $this;
    }
}
