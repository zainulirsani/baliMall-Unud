<?php

namespace App\Entity;

use App\Repository\UserBpdBindingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserBpdBindingRepository::class)
 */
class UserBpdBinding
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
    private $cpan;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $binding_token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ott;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCpan(): ?string
    {
        return $this->cpan;
    }

    public function setCpan(string $cpan): self
    {
        $this->cpan = $cpan;

        return $this;
    }

    public function getBindingToken(): ?string
    {
        return $this->binding_token;
    }

    public function setBindingToken(string $binding_token): self
    {
        $this->binding_token = $binding_token;

        return $this;
    }

    public function getOtt(): ?string
    {
        return $this->ott;
    }

    public function setOtt(string $ott): self
    {
        $this->ott = $ott;

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

}
