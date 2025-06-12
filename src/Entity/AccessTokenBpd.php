<?php

namespace App\Entity;

use DateTime;
use App\Repository\AccessTokenBpdRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AccessTokenBpdRepository::class)
 */
class AccessTokenBpd
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $token;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expiredDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $orderSharedId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getExpiredDate(): ?\DateTimeInterface
    {
        return $this->expiredDate;
    }

    public function setExpiredDate(\DateTimeInterface $expiredDate): self
    {
        $this->expiredDate = $expiredDate;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTime('now');

    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTime('now');
    }

    public function getOrderSharedId(): ?string
    {
        return $this->orderSharedId;
    }

    public function setOrderSharedId(?string $orderSharedId): self
    {
        $this->orderSharedId = $orderSharedId;

        return $this;
    }
}
