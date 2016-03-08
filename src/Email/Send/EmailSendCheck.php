<?php

namespace TonicHealthCheck\Check\Email\Send;

use DateTime;
use Doctrine\ORM\EntityManager;
use Swift_Mailer;
use Swift_Message;
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

            // Create a message
            $emailSendCheck = new EmailSendReceive();

            $emailSendCheck->setFrom($this->getFrom());
            $emailSendCheck->setTo($this->getToSubject());
            $emailSendCheck->setBody(static::MESSAGE_BODY);

            $this->getDoctrine()->persist($emailSendCheck);
            $this->getDoctrine()->flush();

            $emailSendCheck->setSubject($this->genEmailSubject($emailSendCheck));

            $message = Swift_Message::newInstance($emailSendCheck->getSubject())
                ->setFrom($emailSendCheck->getFrom())
                ->setTo($emailSendCheck->getTo())
                ->setBody($emailSendCheck->getBody());

            // Send the message
            try {
                $failedRecipients = [];
                $numSent = $this->getMailer()->send($message, $failedRecipients);
                $this->getMailer()->getTransport()->stop();
            } catch (Swift_SwiftException $e) {
                $emailSendCheck->setStatus(EmailSendReceive::STATUS_SAND_ERROR);
                $this->getDoctrine()->persist($emailSendCheck);
                $this->getDoctrine()->flush();
                throw EmailSendCheckException::internalProblem($e);
            }

            if (!$numSent) {
                $emailSendCheck->setStatus(EmailSendReceive::STATUS_SAND_ERROR);
                $this->getDoctrine()->persist($emailSendCheck);
                $this->getDoctrine()->flush();
                throw EmailSendCheckException::doesNotSendMessage(array_keys($failedRecipients));
            }

            $emailSendCheck->setStatus(EmailSendReceive::STATUS_SANDED);
            $emailSendCheck->setSentAt(new DateTime());
            $this->getDoctrine()->persist($emailSendCheck);
            $this->getDoctrine()->flush();
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
        return sprintf('%s:#%d', $this->getCheckIdent(), $emailSendCheck->getId());
    }

    /**
     * @param int $sendInterval
     */
    protected function setSendInterval($sendInterval)
    {
        $this->sendInterval = $sendInterval;
    }
}
