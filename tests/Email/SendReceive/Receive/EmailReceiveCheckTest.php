<?php

namespace TonicHealthCheck\Tests\Check\Email\Receive;

use DateTime;
use Exception;
use PhpImap\Mailbox;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use PhpImap\Exception as ImapException;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionInterface;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionToFile;
use TonicHealthCheck\Check\Email\Receive\EmailReceiveCheck;
use TonicHealthCheck\Check\Email\Receive\EmailReceiveCheckException;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;

/**
 * Class EmailReceiveCheckTest
 * @package TonicHealthCheck\Tests\Elasticsearch\GetDocument
 */
class EmailReceiveCheckTest extends PHPUnit_Framework_TestCase
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
     * set up
     */
    public function setUp()
    {
        $this->setMailbox($this->getMockBuilder(Mailbox::class)->disableOriginalConstructor()->getMock());

        $this->setPersistCollection(new PersistCollectionToFile(sys_get_temp_dir()));

        $this->setEmailReceiveCheck(new EmailReceiveCheck(
            'testnode',
            $this->getMailbox(),
            $this->getPersistCollection(),
            300
        ));

        parent::setUp();
    }

    /**
     * Test is ok
     */
    public function testCheckIsOk()
    {
        $this->setUpEntity('now');

        $this->setUpGetMailBoxMock();

        $checkResult = $this->getEmailReceiveCheck()->performCheck();

        $this->assertTrue($checkResult->isOk());
        $this->assertNull($checkResult->getError());
    }

    /**
     * Test is fail with exception
     */
    public function testSearchMailboxThrowException()
    {
        $this->setUpEntity();

        $exceptionMsg = 'Error msg text';
        $exceptionCode = 124999;

        $imapException = new ImapException($exceptionMsg, $exceptionCode);

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willThrowException($imapException);

        $checkResult = $this->getEmailReceiveCheck()->performCheck();

        $this->assertFalse($checkResult->isOk());
        $this->assertEquals(EmailReceiveCheckException::CODE_INTERNAL_PROBLE, $checkResult->getError()->getCode());
        $this->assertStringEndsWith($exceptionMsg, $checkResult->getError()->getMessage());
        $this->assertInstanceOf(
            EmailReceiveCheckException::class,
            $checkResult->getError()
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 32453
     *
     * Test is fail with unexpected exception
     */
    public function testSearchMailboxThrowUnexpectedException()
    {
        $this->setUpEntity();

        $exceptionMsg = 'Unexpected error msg text';
        $exceptionCode = 32453;

        $exception = new Exception($exceptionMsg, $exceptionCode);

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willThrowException($exception);

        $this->getEmailReceiveCheck()->performCheck();
    }

    /**
     * Test is fail with exception
     */
    public function testThrowReceivingMaxTimeExpireException()
    {

        $this->setUpEntity('now');

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willReturn([1, 2, 3, 4, 5]);

        $checkResultFirst = $this->getEmailReceiveCheck()->performCheck();
        $this->setUpEntity();
        $checkResultSecond = $this->getEmailReceiveCheck()->performCheck();

        $this->assertTrue($checkResultFirst->isOk());
        $this->assertFalse($checkResultSecond->isOk());
        $this->assertEquals(EmailReceiveCheckException::CODE_RECEIVING_MAX_TIME_EXPIRE, $checkResultSecond->getError()->getCode());
        $this->assertInstanceOf(
            EmailReceiveCheckException::class,
            $checkResultSecond->getError()
        );
    }

    /**
     * @return EmailReceiveCheck
     */
    public function getEmailReceiveCheck()
    {
        return $this->emailReceiveCheck;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getMailbox()
    {
        return $this->mailbox;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return PersistCollectionInterface
     */
    public function getPersistCollection()
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

    private function setUpEntity($sentAt = '-1 day')
    {

        $emailSendReceive = new EmailSendReceive();

        $emailSendReceive->setSentAt(new DateTime($sentAt));
        $emailSendReceive->setStatus(EmailSendReceive::STATUS_SANDED);


        $emailSendReceiveColl = new EmailSendReceiveCollection();
        $emailSendReceiveColl->add($emailSendReceive);

        $this->getPersistCollection()->persist($emailSendReceiveColl);
        $this->getPersistCollection()->flush();

    }

    private function setUpGetMailBoxMock()
    {
        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willReturn([1, 2, 3, 4, 5]);
    }
}
