<?php

namespace TonicHealthCheck\Check\Email\Send;

use DateTime;
use Doctrine\ORM\EntityManager;
use Swift_Mailer;
use Swift_Message;
use Swift_Mime_Message;
use Swift_Mime_MimePart;
use Swift_SwiftException;
use TonicHealthCheck\Check\Email\AbstractEmailCheck;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;

/**
 * Class EmailSendCheck
 * @package TonicHealthCheck\Check\Email\Send
 */
class EmailSendCheck extends AbstractEmailCheck
{
    const CHECK = 'email-send-check';
    const MESSAGE_BODY = 'This is a test, you don\'t need to reply this massage.';
    const SEND_INTERVAL = 600;
    const SUBJECT_TEMPLATE = '%s:#%d';

    /**
     * @var int
     */
    private $sendInterval;

    /**
     * @var Swift_Mailer $client
     */
    private $mailer;

    /**
     * @var EntityManager
     */
    private $doctrine;

    /**
     * @var string;
     */
    private $from;

    /**
     * @var string;
     */
    private $toSubject;

    /**
     * @param string        $checkNode
     * @param Swift_Mailer  $mailer
     * @param EntityManager $doctrine
     * @param string        $from
     * @param string        $toSubjects
     * @param int           $sendInterval
     */
    public function __construct(
        $checkNode,
        Swift_Mailer $mailer,
        EntityManager $doctrine,
        $from,
        $toSubjects,
        $sendInterval = self::SEND_INTERVAL
    ) {
        parent::__construct($checkNode);

        $this->setMailer($mailer);
        $this->setDoctrine($doctrine);
        $this->setFrom($from);
        $this->setToSubject($toSubjects);
        $this->setSendInterval($sendInterval);
    }

    /**
     * Check email can send and receive messages
     * @return bool|void
     * @throws EmailSendCheckException
     */
    public function check()
    {
        $emailSendReceiveR = $this->getDoctrine()->getRepository(EmailSendReceive::class);
        $lastSandedEmail = $emailSendReceiveR->findOneBy([], ['sentAt' => 'DESC']);
        if (null === $lastSandedEmail
            || empty($lastSandedEmail->getSentAt())
            || (time() - $lastSandedEmail->getSentAt()->getTimestamp()) > $this->getSendInterval()
        ) {
            $emailSendCheck = $this->createEmailSendReceive();

            $this->performSend($emailSendCheck);
        }
    }

    /**
     * @return Swift_Mailer
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @return EntityManager
     */
    public function getDoctrine()
    {
        return $this->doctrine;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getToSubject()
    {
        return $this->toSubject;
    }

    /**
     * @return int
     */
    public function getSendInterval()
    {
        return $this->sendInterval;
    }

    /**
     * @param Swift_Mailer $mailer
     */
    protected function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param EntityManager $doctrine
     */
    protected function setDoctrine(EntityManager $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param string $from
     */
    protected function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * @param string $toSubject
     */
    protected function setToSubject($toSubject)
    {
        $this->toSubject = $toSubject;
    }

    /**
     * @param EmailSendReceive $emailSendCheck
     * @return string
     */
    protected function genEmailSubject(EmailSendReceive $emailSendCheck)
    {
        return sprintf(static::SUBJECT_TEMPLATE, $this->getIndent(), $emailSendCheck->getId());
    }

    /**
     * @param int $sendInterval
     */
    protected function setSendInterval($sendInterval)
    {
        $this->sendInterval = $sendInterval;
    }

    /**
     * @return EmailSendReceive
     */
    protected function createEmailSendReceive()
    {
        $emailSendCheck = new EmailSendReceive();

        $emailSendCheck->setFrom($this->getFrom());
        $emailSendCheck->setTo($this->getToSubject());
        $emailSendCheck->setBody(static::MESSAGE_BODY);

        $this->saveEmailSendReceive($emailSendCheck);

        $emailSendCheck->setSubject($this->genEmailSubject($emailSendCheck));

        $this->saveEmailSendReceive($emailSendCheck);

        return $emailSendCheck;
    }

    /**
     * @param Swift_Mime_Message $message
     * @param EmailSendReceive   $emailSendCheck
     * @throws EmailSendCheckException
     */
    protected function sendMessage(Swift_Mime_Message $message, EmailSendReceive $emailSendCheck)
    {
        $failedRecipients = [];
        $numSent = $this->getMailer()->send($message, $failedRecipients);
        $this->getMailer()->getTransport()->stop();
        if (!$numSent) {
            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SAND_ERROR);
            $this->saveEmailSendReceive($emailSendCheck);
            throw EmailSendCheckException::doesNotSendMessage(array_keys($failedRecipients));
        }
    }

    /**
     * @param EmailSendReceive $emailSendCheck
     * @return Swift_Mime_MimePart
     */
    protected function buildMessage(EmailSendReceive $emailSendCheck)
    {
        $message = Swift_Message::newInstance($emailSendCheck->getSubject())
            ->setFrom($emailSendCheck->getFrom())
            ->setTo($emailSendCheck->getTo())
            ->setBody($emailSendCheck->getBody());

        return $message;
    }

    /**
     * @param EmailSendReceive $emailSendCheck
     */
    private function saveEmailSendReceive(EmailSendReceive $emailSendCheck)
    {
        $this->getDoctrine()->persist($emailSendCheck);
        $this->getDoctrine()->flush();
    }

    /**
     * @param EmailSendReceive $emailSendCheck
     * @throws EmailSendCheckException
     */
    private function performSend(EmailSendReceive $emailSendCheck)
    {
        $message = $this->buildMessage($emailSendCheck);

        try {
            $this->sendMessage($message, $emailSendCheck);
            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SANDED);
            $emailSendCheck->setSentAt(new DateTime());
            $this->saveEmailSendReceive($emailSendCheck);
        } catch (Swift_SwiftException $e) {
            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SAND_ERROR);
            $this->saveEmailSendReceive($emailSendCheck);
            throw EmailSendCheckException::internalProblem($e);
        }
    }
}
