<?php

namespace App\Entity;

use App\Helper\StaticHelper;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chat_message", indexes={
 *     @ORM\Index(name="chat_room_idx", columns={"room"}),
 *     @ORM\Index(name="chat_sender_idx", columns={"sender"}),
 *     @ORM\Index(name="chat_recipient_idx", columns={"recipient"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ChatMessageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ChatMessage extends BaseEntity
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
    private $sender;

    /**
     * @ORM\Column(type="boolean")
     */
    private $senderRead;

    /**
     * @ORM\Column(type="bigint")
     */
    private $recipient;

    /**
     * @ORM\Column(type="boolean")
     */
    private $recipientRead;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $deleted;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    public function __construct()
    {
        $this->id = StaticHelper::generateStr();
        $this->senderRead = false;
        $this->recipientRead = false;
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

    public function getSender(): int
    {
        return $this->sender;
    }

    public function setSender(int $sender): void
    {
        $this->sender = $sender;
    }

    public function getSenderRead(): bool
    {
        return $this->senderRead;
    }

    public function setSenderRead(bool $senderRead): void
    {
        $this->senderRead = $senderRead;
    }

    public function getRecipient(): int
    {
        return $this->recipient;
    }

    public function setRecipient(int $recipient): void
    {
        $this->recipient = $recipient;
    }

    public function getRecipientRead(): bool
    {
        return $this->recipientRead;
    }

    public function setRecipientRead(bool $recipientRead): void
    {
        $this->recipientRead = $recipientRead;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getDeleted(): string
    {
        return $this->deleted;
    }

    public function setDeleted(string $deleted): void
    {
        $this->deleted = $deleted;
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
