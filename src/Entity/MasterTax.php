<?php

namespace App\Entity;

use App\Repository\MasterTaxRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="master_tax")
 * @ORM\Entity(repositoryClass=MasterTaxRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class MasterTax
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $ppn;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $pph;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $umkm_category;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPpn(): ?float
    {
        return $this->ppn;
    }

    public function setPpn(?float $ppn): self
    {
        $this->ppn = $ppn;

        return $this;
    }

    public function getPph(): ?float
    {
        return $this->pph;
    }

    public function setPph(?float $pph): self
    {
        $this->pph = $pph;

        return $this;
    }

    public function getUmkmCategory(): ?string
    {
        return $this->umkm_category;
    }

    public function setUmkmCategory(?string $umkm_category): self
    {
        $this->umkm_category = $umkm_category;

        return $this;
    }
}
