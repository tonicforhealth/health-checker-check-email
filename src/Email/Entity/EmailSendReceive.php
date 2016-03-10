<?php

namespace TonicHealthCheck\Check\Email\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * TonicHealthCheck\Entity\EmailSendReceive;
 *
 * @Entity(repositoryClass="EmailSendReceiveRepository")
 * @Table(name="health_check_email")
 * @HasLifecycleCallbacks
 */
class EmailSendReceive
{
    const STATUS_CREATED = 'created';
    const STATUS_SANDED = 'sanded';
    const STATUS_SAND_ERROR = 'sand_error';
    const STATUS_RECEIVED = 'received';
    const STATUS_RECEIVED_ERROR = 'receive_error';
    const STATUS_EXPIRED = 'expired';

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var DateTimeInterface
     * @Column(type="datetime",name="sent_at", nullable=true)
     */
    private $sentAt;

    /**
     * @var DateTimeInterface
     * @Column(type="datetime", name="received_at", nullable=true)
     */
    private $receivedAt;

    /**
     * @Column(type="string", length=512, nullable=true)
     */
    private $subject;

    /**
     * @Column(type="text")
     */
    private $body;

    /**
     * @Column(type="string", name="`from`", length=256)
     */
    private $from;

    /**
     * @Column(type="string", name="`to`", length=256, nullable=true)
     */
    private $to;

    /**
     * @Column(type="string", length=64)
     */
    private $status = self::STATUS_CREATED;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set sentAt
     *
     * @param \DateTime $sentAt
     *
     * @return EmailSendReceive
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * Get sentAt
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set receivedAt
     *
     * @param \DateTime $receivedAt
     *
     * @return EmailSendReceive
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get receivedAt
     *
     * @return \DateTime
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return EmailSendReceive
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return EmailSendReceive
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set from
     *
     * @param string $from
     *
     * @return EmailSendReceive
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param string $to
     *
     * @return EmailSendReceive
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return EmailSendReceive
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
