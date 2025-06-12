<?php

namespace App\Entity;

use App\Repository\KldiRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=KldiRepository::class)
 */
class Kldi
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $kldi_name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $digitVa;

    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
     private $id_lpse;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKldiName(): ?string
    {
        return $this->kldi_name;
    }

    public function setKldiName(?string $kldi_name): self
    {
        $this->kldi_name = $kldi_name;

        return $this;
    }

    public function getDigitVa(): ?string
    {
        return $this->digitVa;
    }

    public function setDigitVa(?string $digitVa): self
    {
        $this->digitVa = $digitVa;

        return $this;
    }

    public function setIdLpse(?int $id_lpse): self
    {
        $this->id_lpse = $id_lpse;
        
        return $this;
    }

    public function getIdLpse(): ?int
    {
        return $this->id_lpse;
    }
}
