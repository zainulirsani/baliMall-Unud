<?php

namespace App\Entity;

use App\Helper\StaticHelper;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chat", indexes={
 *     @ORM\Index(name="chat_room_idx", columns={"room"}),
 *     @ORM\Index(name="chat_initiator_idx", columns={"initiator"}),
 *     @ORM\Index(name="chat_participant_idx", columns={"participant"}),
 *     @ORM\Index(name="chat_order_id_idx", columns={"order_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ChatRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Chat extends BaseEntity
{
    /**
     * @ORM\Column(type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $room;

    /**
     * @ORM\Column(type="bigint")
     */
    private $initiator;

    /**
     * @ORM\Column(type="bigint")
     */
    private $participant;

    /**
     * @ORM\Column(type="bigint")
     */
    private $orderId;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    public function __construct()
    {
        $this->id = StaticHelper::generateStr();
        $this->orderId = 0;
        $this->type = 'direct';
        $this->status = 'offline';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoom(): string
    {
        return $this->room;
    }

    public function setRoom(string $room): void
    {
        $this->room = $room;
    }

    public function getInitiator(): int
    {
        return $this->initiator;
    }

    public function setInitiator(int $initiator): void
    {
        $this->initiator = $initiator;
    }

    public function getParticipant(): int
    {
        return $this->participant;
    }

    public function setParticipant(int $participant): void
    {
        $this->participant = $participant;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
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
