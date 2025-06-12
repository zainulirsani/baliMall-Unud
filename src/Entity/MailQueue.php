<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Table(name="mail_queue", indexes={
 *     @ORM\Index(name="mail_queue_connection_idx", columns={"connection"}),
 *     @ORM\Index(name="mail_queue_batch_idx", columns={"batch"}),
 *     @ORM\Index(name="mail_queue_entity_id_idx", columns={"entity_id"}),
 *     @ORM\Index(name="mail_queue_entity_name_idx", columns={"entity_name"})
 * }))
 * @ORM\Entity(repositoryClass="App\Repository\MailQueueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class MailQueue extends BaseEntity
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @ORM\Column(type="string", options={"default": "default"})
     */
    private $connection;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $batch;

    /**
     * @ORM\Column(type="bigint", options={"default": "0"})
     */
    private $entityId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $entityName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $payload;

    /**
     * @ORM\Column(type="integer", options={"default": "0"})
     */
    private $success;

    /**
     * @ORM\Column(type="integer", options={"default": "0"})
     */
    private $failed;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
        $this->connection = 'default';
        $this->entityId = 0;
        $this->success = 0;
        $this->failed = 0;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    public function getBatch(): string
    {
        return $this->batch;
    }

    public function setBatch(string $batch): void
    {
        $this->batch = $batch;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    public function getEntityName()
    {
        return $this->entityName;
    }

    public function setEntityName(?string $entityName): void
    {
        $this->entityName = $entityName;
    }

    public function getPayload(): array
    {
        return !empty($this->payload) ? json_decode($this->payload, true) : [];
    }

    public function setPayload(array $payload = []): void
    {
        $this->payload = json_encode($payload);
    }

    public function getSuccess(): int
    {
        return $this->success;
    }

    public function setSuccess(int $success): void
    {
        $this->success = $success;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): void
    {
        $this->failed = $failed;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTime('now');
    }
}
