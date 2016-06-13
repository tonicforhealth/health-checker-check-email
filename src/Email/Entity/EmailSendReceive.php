<?php

namespace TonicHealthCheck\Check\Email\Entity;

use DateTime;
use DateTimeInterface;

/**
 * Class EmailSendReceive
 * @package TonicHealthCheck\Check\Email\Entity
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
     * @var DateTimeInterface
     */
    private $sentAt;

    /**
     * @var DateTimeInterface
     */
    private $receivedAt;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var string
     */
    private $status = self::STATUS_CREATED;

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
