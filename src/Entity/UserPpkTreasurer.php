<?php

namespace App\Entity;

use DateTime;
use App\Repository\UserPpkTreasurerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserPpkTreasurerRepository::class)
 */
class UserPpkTreasurer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="userPpkTreasurers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated_at;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $type_account;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $telp;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $userAccount;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $kldi;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $satker;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getNip(): ?string
    {
        return $this->nip;
    }

    public function setNip(string $nip): self
    {
        $this->nip = $nip;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(): self
    {
        $this->created_at = new DateTime('now');

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(): self
    {
        $this->updated_at = new DateTime('now');

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getTypeAccount(): ?string
    {
        return $this->type_account;
    }

    public function setTypeAccount(?string $type_account): self
    {
        $this->type_account = $type_account;

        return $this;
    }

    public function getTelp(): ?string
    {
        return $this->telp;
    }

    public function setTelp(?string $telp): self
    {
        $this->telp = $telp;

        return $this;
    }

    public function getUserAccount(): ?int
    {
        return $this->userAccount;
    }

    public function setUserAccount(?int $userAccount): self
    {
        $this->userAccount = $userAccount;

        return $this;
    }

    public function getKldi(): ?string
    {
        return $this->kldi;
    }

    public function setKldi(?string $kldi): self
    {
        $this->kldi = $kldi;

        return $this;
    }

    public function getSatker(): ?string
    {
        return $this->satker;
    }

    public function setSatker(?string $satker): self
    {
        $this->satker = $satker;

        return $this;
    }
}
