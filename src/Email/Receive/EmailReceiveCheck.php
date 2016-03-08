<?php

namespace TonicHealthCheck\Check\Email\Receive;

use DateTime;
use Doctrine\ORM\EntityManager;
use PhpImap\Mailbox;
use TonicHealthCheck\Check\Email\AbstractEmailCheck;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use PhpImap\Exception as ImapException;

/**
 * Class EmailReceiveCheck
 * @package TonicHealthCheck\Check\Email\Send
 */
class EmailReceiveCheck extends AbstractEmailCheck
{
    const CHECK = 'email-receive-check';
    const RECEIVE_MAX_TIME = 300;

    /**
     * @var bool
     */
    private $firstFailSkip = true;

    /**
     * @var int
     */
    private $receiveMaxTime;

    /**
     * @var Mailbox;
     */
    private $mailbox;


    /**
     * @var EntityManager
     */
    private $doctrine;

    /**
     * @param string        $checkNode
     * @param Mailbox       $mailbox
     * @param EntityManager $doctrine
     * @param int           $receiveMaxTime
     */
    public function __construct(
        $checkNode,
        Mailbox $mailbox,
        EntityManager $doctrine,
        $receiveMaxTime = self::RECEIVE_MAX_TIME
    ) {
        parent::__construct($checkNode);

        $this->setMailbox($mailbox);
        $this->setDoctrine($doctrine);
        $this->setReceiveMaxTime($receiveMaxTime);
    }

    /**
     * Check email can send and receive messages
     * @return bool|void
     * @throws EmailReceiveCheckException
     */
    public function check()
    {
        $emailSendReceiveR = $this->getDoctrine()->getRepository(EmailSendReceive::class);

        /** @var EmailSendReceive $emailSendCheckI */
        foreach ($emailSendReceiveR->findBy(
            ['status' => EmailSendReceive::STATUS_SANDED]
        ) as $emailSendCheckI) {
            try {
                $mails = $this->getMailbox()->searchMailbox(
                    'FROM '.$emailSendCheckI->getFrom().' SUBJECT '.$emailSendCheckI->getSubject()
                );
                if (count($mails) > 0) {
                    $this->deleteReceivedEmails($mails, $emailSendCheckI);
                    $this->timeReceiveCheck($emailSendCheckI);
                }
            } catch (ImapException $e) {
                $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED_ERROR);
                $this->saveEmailSendReceive($emailSendCheckI);
                throw EmailReceiveCheckException::internalProblem($e);
            }
        }
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

    /**
     * @param EmailSendReceive $emailSendCheckI
     * @throws EmailReceiveCheckException
     */
    protected function timeReceiveCheck(EmailSendReceive $emailSendCheckI)
    {
        $timeLeft = time() - $emailSendCheckI->getSentAt()->getTimestamp();
        if ($timeLeft > $this->getReceiveMaxTime()) {

            $emailSendCheckI->setStatus(EmailSendReceive::STATUS_EXPIRED);
            $emailSendCheckI->setReceivedAt(new DateTime());
            $this->saveEmailSendReceive($emailSendCheckI);

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

    /**
     * @param EmailSendReceive $emailSendCheckI
     */
    private function saveEmailSendReceive(EmailSendReceive $emailSendCheckI)
    {
        $this->getDoctrine()->persist($emailSendCheckI);
        $this->getDoctrine()->flush();
    }

    /**
     * @param $mails
     * @param EmailSendReceive $emailSendCheckI
     */
    private function deleteReceivedEmails($mails, EmailSendReceive $emailSendCheckI)
    {
        foreach ($mails as $mailId) {
            $this->getMailbox()->deleteMail($mailId);
            $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED);
            $this->saveEmailSendReceive($emailSendCheckI);
        }
    }
}
