<?php

namespace App\Entity;

use App\Repository\SatkerDetailRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SatkerDetailRepository::class)
 */
class SatkerDetail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id_satker;

    /**
     * @ORM\Column(type="string", length=29, nullable=true)
     */
    private $kode;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $satker_name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $id_lpse;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSatker(): ?int
    {
        return $this->id_satker;
    }

    public function setIdSatker(int $id_satker): self
    {
        $this->id_satker = $id_satker;

        return $this;
    }

    public function getKode(): ?string
    {
        return $this->kode;
    }

    public function setKode(?string $kode): self
    {
        $this->kode = $kode;

        return $this;
    }

    public function getSatkerName(): ?string
    {
        return $this->satker_name;
    }

    public function setSatkerName(string $satker_name): self
    {
        $this->satker_name = $satker_name;

        return $this;
    }

    public function getIdLpse(): ?int
    {
        return $this->id_lpse;
    }

    public function setIdLpse(?int $id_lpse): self
    {
        $this->id_lpse = $id_lpse;

        return $this;
    }
}
