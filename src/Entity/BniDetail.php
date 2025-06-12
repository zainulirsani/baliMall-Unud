<?php

namespace App\Entity;

use DateTime;
use App\Repository\BniDetailRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BniDetailRepository::class)
 */
class BniDetail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $order_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $bni_trx_id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    public function setOrderId(?int $order_id): self
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getBniTrxId(): ?int
    {
        return $this->bni_trx_id;
    }

    public function setBniTrxId(?int $bni_trx_id): self
    {
        $this->bni_trx_id = $bni_trx_id;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;

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
}
