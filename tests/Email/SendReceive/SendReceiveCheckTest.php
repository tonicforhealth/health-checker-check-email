<?php

namespace TonicHealthCheck\Tests\Check\Email;

use DateTime;
use PhpImap\Mailbox;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Swift_Mime_Message;
use Swift_SmtpTransport;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionInterface;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionToFile;
use TonicHealthCheck\Check\Email\Receive\EmailReceiveCheck;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;
use TonicHealthCheck\Check\Email\SendReceiveCheck;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\Success;
use PhpImap\Exception as ImapException;

/**
 * Class SendReceiveCheckTest
 */
class SendReceiveCheckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EmailReceiveCheck;
     */
    private $emailReceiveCheck;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $transport;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $mailbox;

    /**
     * @var PersistCollectionInterface;
     */
    private $persistCollection;

    /**
     * @var EmailSendCheck;
     */
    private $emailSendCheck;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $mailer;

    /**
     * @var SendReceiveCheck
     */
    private $sendReceiveCheck;


    /**
     * set up
     */
    public function setUp()
    {
        $this->setPersistCollection(new PersistCollectionToFile(sys_get_temp_dir()));

        $this->setUpSend();
        $this->setUpReceive();

        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $this->setSendReceiveCheck(new SendReceiveCheck(
            $logger,
            $this->getEmailSendCheck(),
            $this->getEmailReceiveCheck()
        ));
    }

    /**
     * set up
     */
    public function setUpSend()
    {
        $this->setTransport($this->getMockBuilder(Swift_SmtpTransport::class)->getMock());

        $this->setMailer(
            $this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock()
        );

        $this
            ->getMailer()
            ->method('getTransport')
            ->willReturn($this->getTransport());

        $this->setEmailSendCheck(new EmailSendCheck(
            'testnode',
            $this->getMailer(),
            $this->getPersistCollection(),
            'test@test.com',
            'to_test@test.com',
            600
        ));

        parent::setUp();
    }

    /**
     * set up
     */
    public function setUpReceive()
    {
        $this->setMailbox($this->getMockBuilder(Mailbox::class)->disableOriginalConstructor()->getMock());

        $this->setEmailReceiveCheck(new EmailReceiveCheck(
            'testnode',
            $this->getMailbox(),
            $this->getPersistCollection(),
            300
        ));

        parent::setUp();
    }

    /**
     * test is ok
     */
    public function testCheckIsOk()
    {
        $this->setUpEntity('now');
        $this->setUpSendMock();
        $this->setUpGetMailBoxMock();
        $result = $this->getSendReceiveCheck()->check();


        $this->assertInstanceOf(Success::class, $result);

    }

    /**
     * test is send fail
     */
    public function testCheckIsSendFail()
    {
        $this->setUpEntity();

        $this->setUpGetMailBoxMock();

        $result = $this->getSendReceiveCheck()->check();


        $this->assertInstanceOf(Failure::class, $result);
    }

    /**
     * test is receive fail
     */
    public function testCheckIsReceiveFail()
    {
        $this->setUpEntity();

        $this->setUpSendMock();

        $exceptionMsg = 'Error msg text';
        $exceptionCode = 124999;

        $imapException = new ImapException($exceptionMsg, $exceptionCode);

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willThrowException($imapException);

        $result = $this->getSendReceiveCheck()->check();


        $this->assertInstanceOf(Failure::class, $result);
    }

    /**
     * test label
     */
    public function testLabel()
    {
        $label = $this->getEmailSendCheck()->getIndent().' <-> '.$this->getEmailReceiveCheck()->getIndent();
        $this->assertEquals($label, $this->getSendReceiveCheck()->getLabel());
    }



    /**
     * @return EmailReceiveCheck
     */
    protected function getEmailReceiveCheck()
    {
        return $this->emailReceiveCheck;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMailbox()
    {
        return $this->mailbox;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return PersistCollectionInterface
     */
    protected function getPersistCollection()
    {
        return $this->persistCollection;
    }

    /**
     * @param EmailReceiveCheck $emailReceiveCheck
     */
    protected function setEmailReceiveCheck(EmailReceiveCheck $emailReceiveCheck)
    {
        $this->emailReceiveCheck = $emailReceiveCheck;
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $mailbox
     */
    protected function setMailbox(PHPUnit_Framework_MockObject_MockObject $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $mailer
     */
    protected function setMailer(PHPUnit_Framework_MockObject_MockObject $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject $transport
     */
    protected function setTransport(PHPUnit_Framework_MockObject_MockObject $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param PersistCollectionInterface $persistCollection
     */
    protected function setPersistCollection(PersistCollectionInterface $persistCollection)
    {
        $this->persistCollection = $persistCollection;
    }

    /**
     * @return EmailSendCheck
     */
    protected function getEmailSendCheck()
    {
        return $this->emailSendCheck;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMailer()
    {
        return $this->mailer;
    }


    /**
     * @param EmailSendCheck $emailSendCheck
     * @internal param EmailSendCheck $emailSendCheck
     */
    protected function setEmailSendCheck(EmailSendCheck $emailSendCheck)
    {
        $this->emailSendCheck = $emailSendCheck;
    }

    /**
     * @return SendReceiveCheck
     */
    protected function getSendReceiveCheck()
    {
        return $this->sendReceiveCheck;
    }

    /**
     * @param SendReceiveCheck $sendReceiveCheck
     */
    protected function setSendReceiveCheck(SendReceiveCheck $sendReceiveCheck)
    {
        $this->sendReceiveCheck = $sendReceiveCheck;
    }

    private function setUpEntity($sentAt = '-1 day')
    {

        $emailSendReceive = new EmailSendReceive();

        $emailSendReceive->setSentAt(new DateTime($sentAt));


        $emailSendReceiveColl = new EmailSendReceiveCollection();
        $emailSendReceiveColl->add($emailSendReceive);

        $this->getPersistCollection()->persist($emailSendReceiveColl);
        $this->getPersistCollection()->flush();

    }

    /**
     * set up get mail box mock
     */
    private function setUpGetMailBoxMock()
    {
        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willReturn([1, 2, 3, 4, 5]);
    }

    /**
     * set ip send mock
     */
    private function setUpSendMock()
    {
        $this
            ->getMailer()
            ->method('send')
            ->willReturnCallback(
                function (Swift_Mime_Message $message, &$failedRecipients = null) {
                    $failedRecipients = ['test@test.com' => true];

                    return true;
                }
            );
    }
}

