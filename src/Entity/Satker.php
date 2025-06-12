<?php

namespace App\Entity;

use App\Repository\SatkerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SatkerRepository::class)
 */
class Satker
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
    private $satkerName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $digitVa;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="satkers")
     */
    private $user;

     /**
     * @ORM\Column(type="integer", nullable=true, name="id_lpse")
     */
    private $idLpse;

     /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $idSatker;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSatkerName(): ?string
    {
        return $this->satkerName;
    }

    public function setSatkerName(string $satkerName): self
    {
        $this->satkerName = $satkerName;

        return $this;
    }

    public function getDigitVa(): ?string
    {
        return $this->digitVa;
    }

    public function setDigitVa(string $digitVa): self
    {
        $this->digitVa = $digitVa;

        return $this;
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

    public function getIdLpse(): ?int
    {
        return $this->idLpse;
    }

    public function setIdLpse(?int $idLpse): self
    {
        $this->idLpse = $idLpse;

        return $this;
    }

    public function getIdSatker(): ?string
    {
        return $this->idSatker;
    }

    public function setIdSatker(string $idSatker): self
    {
        $this->idSatker = $idSatker;

        return $this;
    }
}
