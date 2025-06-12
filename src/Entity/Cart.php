<?php

namespace App\Entity;

use App\Helper\StaticHelper;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="cart")
 * @ORM\Entity(repositoryClass="App\Repository\CartRepository")
 */
class Cart extends BaseEntity
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * One Cart <==> One User.
     * @ORM\OneToOne(targetEntity="App\Entity\User", inversedBy="cart", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $user;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $reference;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    public function __construct()
    {
        $this->id = StaticHelper::generateStr();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }
}
