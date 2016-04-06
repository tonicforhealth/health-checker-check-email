<?php

namespace TonicHealthCheck\Check\Email\Receive;

use DateTime;
use Doctrine\ORM\EntityManager;
use PhpImap\Mailbox;
use TonicHealthCheck\Check\Email\AbstractEmailCheck;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use PhpImap\Exception as ImapException;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionInterface;

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
     * @var PersistCollectionInterface
     */
    private $persistCollection;

    /**
     * @var EmailSendReceiveCollection
     */
    private $emailSendReceiveCollection;

    /**
     * @param string                     $checkNode
     * @param Mailbox                    $mailbox
     * @param PersistCollectionInterface $persistCollection
     * @param int                        $receiveMaxTime
     */
    public function __construct(
        $checkNode,
        Mailbox $mailbox,
        PersistCollectionInterface $persistCollection,
        $receiveMaxTime = self::RECEIVE_MAX_TIME
    ) {
        parent::__construct($checkNode);

        $this->setMailbox($mailbox);
        $this->setPersistCollection($persistCollection);
        $this->setReceiveMaxTime($receiveMaxTime);
    }

    /**
     * Check email can send and receive messages
     * @return bool|void
     * @throws EmailReceiveCheckException
     */
    public function check()
    {
        $this->setEmailSendReceiveColl($this->getPersistCollection()->load());

        /** @var EmailSendReceive $emailSendCheckI */
        foreach ($this->getEmailSendReceiveColl() as $emailSendCheckI) {
            $this->performReceive($emailSendCheckI);
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
     * @return PersistCollectionInterface
     */
    public function getPersistCollection()
    {
        return $this->persistCollection;
    }

    /**
     * @return int
     */
    public function getReceiveMaxTime()
    {
        return $this->receiveMaxTime;
    }

    /**
     * @return EmailSendReceiveCollection
     */
    public function getEmailSendReceiveColl()
    {
        return $this->emailSendReceiveCollection;
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
     * @param PersistCollectionInterface $persistCollection
     */
    protected function setPersistCollection(PersistCollectionInterface $persistCollection)
    {
        $this->persistCollection = $persistCollection;
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
     * @param EmailSendReceiveCollection $emailSendReceiveC
     */
    protected function setEmailSendReceiveColl(EmailSendReceiveCollection $emailSendReceiveC)
    {
        $this->emailSendReceiveCollection = $emailSendReceiveC;
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
            $this->getEmailSendReceiveColl()->remove(
                $this->findSameItemCallback($emailSendCheckI)
            );
            $this->getPersistCollection()->flush();

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
     * @param $mails
     * @param EmailSendReceive $emailSendCheckI
     */
    private function deleteReceivedEmails($mails, EmailSendReceive $emailSendCheckI)
    {
        foreach ($mails as $mailId) {
            $this->getMailbox()->deleteMail($mailId);
            $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED);
            $this->getEmailSendReceiveColl()->remove(
                $this->findSameItemCallback($emailSendCheckI)
            );
            $this->getPersistCollection()->flush();
        }
    }

    /**
     * @param EmailSendReceive $emailSendCheckI
     * @throws EmailReceiveCheckException
     */
    private function performReceive(EmailSendReceive $emailSendCheckI)
    {
        try {
            $mails = $this->getMailbox()->searchMailbox(
                'FROM '.$emailSendCheckI->getFrom().' SUBJECT '.$emailSendCheckI->getSubject()
            );

            $this->timeReceiveCheck($emailSendCheckI);

            if (count($mails) > 0) {
                $this->deleteReceivedEmails($mails, $emailSendCheckI);
            }

        } catch (ImapException $e) {
            $emailSendCheckI->setStatus(EmailSendReceive::STATUS_RECEIVED_ERROR);
            $this->getEmailSendReceiveColl()->remove(
                $this->findSameItemCallback($emailSendCheckI)
            );
            $this->getPersistCollection()->flush();
            throw EmailReceiveCheckException::internalProblem($e);
        }
    }

    /**
     * @param EmailSendReceive $emailSendCheckI
     * @return \Closure
     */
    private function findSameItemCallback(EmailSendReceive $emailSendCheckI)
    {
        return function (EmailSendReceive $emailSendCheckItem) use ($emailSendCheckI) {
            return $emailSendCheckItem === $emailSendCheckI;
        };
    }
}
