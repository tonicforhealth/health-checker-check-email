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
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionInterface;

/**
 * Class EmailSendCheck
 * @package TonicHealthCheck\Check\Email\Send
 */
class EmailSendCheck extends AbstractEmailCheck
{
    const CHECK = 'email-send-check';
    const MESSAGE_BODY = 'This is a test, you don\'t need to reply this massage.';
    const SEND_INTERVAL = 600;
    const SUBJECT_TEMPLATE = '%s:time:%d';

    /**
     * @var int
     */
    private $sendInterval;

    /**
     * @var Swift_Mailer $client
     */
    private $mailer;

    /**
     * @var PersistCollectionInterface
     */
    private $persistCollection;

    /**
     * @var EmailSendReceiveCollection
     */
    private $emailSendReceiveCollection;

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
        PersistCollectionInterface $persistCollection,
        $from,
        $toSubjects,
        $sendInterval = self::SEND_INTERVAL
    ) {
        parent::__construct($checkNode);

        $this->setMailer($mailer);
        $this->setPersistCollection($persistCollection);
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
        $this->setEmailSendReceiveColl($this->getPersistCollection()->load());


        $lastSandedEmail = $this->getEmailSendReceiveColl()->at(
            $this->getEmailSendReceiveColl()->count()-1
        );
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
     * @return PersistCollectionInterface
     */
    public function getPersistCollection()
    {
        return $this->persistCollection;
    }

    /**
     * @return EmailSendReceiveCollection
     */
    public function getEmailSendReceiveColl()
    {
        return $this->emailSendReceiveCollection;
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
     * @param PersistCollectionInterface $persistCollection
     */
    protected function setPersistCollection(PersistCollectionInterface $persistCollection)
    {
        $this->persistCollection = $persistCollection;
    }

    /**
     * @param EmailSendReceiveCollection $emailSendReceiveC
     */
    protected function setEmailSendReceiveColl(EmailSendReceiveCollection $emailSendReceiveC)
    {
        $this->emailSendReceiveCollection = $emailSendReceiveC;
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
     * @return string
     */
    protected function genEmailSubject()
    {
        return sprintf(static::SUBJECT_TEMPLATE, $this->getIndent(), date(DATE_RFC2822));
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
        $emailSendCheck->setSubject($this->genEmailSubject());

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

        $this->getEmailSendReceiveColl()->add($emailSendCheck);
        $this->getPersistCollection()->flush();
    }

    /**
     * @param EmailSendReceive $emailSendCheck
     * @throws EmailSendCheckException
     */
    private function performSend(EmailSendReceive $emailSendCheck)
    {
        $message = $this->buildMessage($emailSendCheck);

        try {
            $emailSendCheck->setSentAt(new DateTime());
            $this->sendMessage($message, $emailSendCheck);
            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SANDED);
            $this->saveEmailSendReceive($emailSendCheck);
        } catch (Swift_SwiftException $e) {
            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SAND_ERROR);
            $this->saveEmailSendReceive($emailSendCheck);
            throw EmailSendCheckException::internalProblem($e);
        }
    }
}
