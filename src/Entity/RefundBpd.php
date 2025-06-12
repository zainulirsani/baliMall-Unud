<?php

namespace App\Entity;

use App\Repository\RefundBpdRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=RefundBpdRepository::class)
 */
class RefundBpd
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=BpdCc::class, inversedBy="refundBpds")
     * @ORM\JoinColumn(nullable=false)
     */
    private $ccBpd;

    /**
     * @ORM\Column(type="text")
     */
    private $requestData;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $response;

    /**
     * @ORM\Column(type="datetime")
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

    public function getCcBpd(): ?BpdCc
    {
        return $this->ccBpd;
    }

    public function setCcBpd(?BpdCc $ccBpd): self
    {
        $this->ccBpd = $ccBpd;

        return $this;
    }

    public function getRequestData(): ?string
    {
        return $this->requestData;
    }

    public function setRequestData(string $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
