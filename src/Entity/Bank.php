<?php

namespace App\Entity;

use DateTime;
use App\Repository\BankRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BankRepository::class)
 */
class Bank
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_accept_transfer;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_accept_va;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $bank_slug;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIsAcceptTransfer(): ?bool
    {
        return $this->is_accept_transfer;
    }

    public function setIsAcceptTransfer(bool $is_accept_transfer): self
    {
        $this->is_accept_transfer = $is_accept_transfer;

        return $this;
    }

    public function getIsAcceptVa(): ?bool
    {
        return $this->is_accept_va;
    }

    public function setIsAcceptVa(bool $is_accept_va): self
    {
        $this->is_accept_va = $is_accept_va;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }

    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTime('now');
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTime('now');
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getBankSlug(): ?string
    {
        return $this->bank_slug;
    }

    public function setBankSlug(string $bank_slug): self
    {
        $this->bank_slug = $bank_slug;

        return $this;
    }
}
