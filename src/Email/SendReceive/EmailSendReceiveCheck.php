<?php

namespace TonicHealthCheck\Check\Email\SendReceive;

use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use PhpImap\Mailbox;
use Swift_Mailer;
use Swift_Message;
use Swift_SwiftException;
use TonicHealthCheck\Check\Email\AbstractEmailCheck;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use PhpImap\Exception as ImapException;

/**
 * Class EmailSendReceiveCheck
 * @package TonicHealthCheck\Check\Email\Send
 */
class EmailSendReceiveCheck extends AbstractEmailCheck
{
    const CHECK = 'email-send-receive-check';
    const RECEIVE_MAX_TIME = 300;
    const MESSAGE_BODY = 'This is a test, you don\'t need to reply this massage.';


    /**
     * @var bool
     */
    private $firstFailSkip = true;

    /**
     * @var int
     */
    private $receiveMaxTime;

    /**
     * @var Swift_Mailer $client
     */
    private $mailer;

    /**
     * @var Mailbox;
     */
    private $mailbox;


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
     * @param Mailbox       $mailbox
     * @param EntityManager $doctrine
     * @param string        $from
     * @param string        $toSubjects
     * @param int           $receiveMaxTime
     */
    public function __construct(
        $checkNode,
        Swift_Mailer $mailer,
        Mailbox $mailbox,
        EntityManager $doctrine,
        $from,
        $toSubjects,
        $receiveMaxTime = self::RECEIVE_MAX_TIME
    ) {
        parent::__construct($checkNode);

        $this->setMailer($mailer);
        $this->setMailbox($mailbox);
        $this->setDoctrine($doctrine);
        $this->setFrom($from);
        $this->setToSubject($toSubjects);
        $this->setReceiveMaxTime($receiveMaxTime);
    }

    /**
     * Check email can send and receive messages
     *
     * @return bool|void
     * @throws AbstractEmailCheck
     */
    public function check()
    {
        $emailSendReceiveRepository = $this->getDoctrine()->getRepository(EmailSendReceive::class);


        $lastSandedEmail = $emailSendReceiveRepository->findOneBy([], ['sentAt' => 'DESC']);
        if (null === $lastSandedEmail || empty($lastSandedEmail->getSentAt()) || time() - $lastSandedEmail->getSentAt()->getTimestamp() > $this->getReceiveMaxTime()) {

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

        /** @var EmailSendReceive $emailSendCheckI */
        foreach ($emailSendReceiveRepository->findBy(['status' => EmailSendReceive::STATUS_SANDED]) as $emailSendCheckI) {
            try {
                $mails = $this->getMailbox()->searchMailbox(
                    'FROM '.$emailSendCheckI->getFrom().' SUBJECT '.$emailSendCheckI->getSubject()
                );
                if (count($mails) > 0) {
                    foreach ($mails as $mailId) {
                        $mail = $this->getMailbox()->getMail($mailId);
                        $this->getMailbox()->deleteMail($mailId);
                        $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED);
                        $this->getDoctrine()->persist($emailSendCheckI);
                        $this->getDoctrine()->flush();
                    }
                    $timeLeft = time() - $emailSendCheckI->getSentAt()->getTimestamp();
                    if ($timeLeft > $this->getReceiveMaxTime()) {

                        $emailSendCheckI->setStatus(EmailSendReceive::STATUS_EXPIRED);
                        $emailSendCheckI->setReceivedAt(new DateTime());
                        $this->getDoctrine()->persist($emailSendCheckI);
                        $this->getDoctrine()->flush();

                        if (!$this->isFirstFailSkip()) {
                            throw EmailReceiveCheckException::receivingMaxTimeExpire(
                                $emailSendCheckI->getSubject(),
                                $timeLeft,
                                $this->getReceiveMaxTime()
                            );
                        } else {

                            $this->setFirstFailSkip(false);
                        }
                    }
                }
            } catch (ImapException $e) {
                $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED_ERROR);
                $this->getDoctrine()->persist($emailSendCheckI);
                $this->getDoctrine()->flush();
                throw EmailReceiveCheckException::internalProblem($e);
            }
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
     * @return Mailbox
     */
    public function getMailbox()
    {
        return $this->mailbox;
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
    public function getReceiveMaxTime()
    {
        return $this->receiveMaxTime;
    }

    /**
     * @return boolean
     */
    public function isFirstFailSkip()
    {
        return $this->firstFailSkip;
    }

    /**
     * @param Swift_Mailer $mailer
     */
    protected function setMailer(Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param Mailbox $mailbox
     */
    protected function setMailbox(Mailbox $mailbox)
    {
        $this->mailbox = $mailbox;
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
     * @param int $receiveMaxTime
     */
    protected function setReceiveMaxTime($receiveMaxTime)
    {
        $this->receiveMaxTime = $receiveMaxTime;
    }

    /**
     * @param boolean $firstFailSkip
     */
    protected function setFirstFailSkip($firstFailSkip)
    {
        $this->firstFailSkip = $firstFailSkip;
    }
}
