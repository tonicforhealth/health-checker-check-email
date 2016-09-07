<?php

namespace TonicHealthCheck\Tests\Check\Email\Send;

use DateTime;
use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Swift_Mailer;
use Swift_Mime_Message;
use Swift_SmtpTransport;
use Swift_SwiftException;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionInterface;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionToFile;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;
use TonicHealthCheck\Check\Email\Send\EmailSendCheckException;

/**
 * Class EmailSendCheckTest.
 */
class EmailSendCheckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EmailSendCheck;
     */
    private $emailSendCheck;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $transport;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $mailer;

    /**
     * @var PersistCollectionInterface;
     */
    private $persistCollection;

    /**
     * set up.
     */
    public function setUp()
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

        $this->setPersistCollection(new PersistCollectionToFile(sys_get_temp_dir()));

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
     * Test is ok.
     */
    public function testCheckIsOk()
    {
        $this->setUpEntity();

        $this->setUpSendMock();

        $checkResult = $this->getEmailSendCheck()->check();

        $this->assertTrue($checkResult->isOk());
        $this->assertNull($checkResult->getError());
    }

    /**
     * Test is fail.
     */
    public function testSendIsFail()
    {
        $this->setUpEntity();

        $this
            ->getMailer()
            ->method('send')
            ->willReturnCallback(
                function (Swift_Mime_Message $message, &$failedRecipients = null) {
                    $failedRecipients = ['test@test.com' => false];

                    return false;
                }
            );

        $checkResult = $this->getEmailSendCheck()->check();

        $this->assertFalse($checkResult->isOk());
        $this->assertInstanceOf(
            EmailSendCheckException::class,
            $checkResult->getError()
        );
        $this->assertEquals(
            EmailSendCheckException::CODE_DOES_NOT_SEND,
            $checkResult->getError()->getCode()
        );
    }

    /**
     * Test is fail with exception.
     */
    public function testSendThrowException()
    {
        $this->setUpEntity();

        $exceptionMsg = 'Error msg text';
        $exceptionCode = 124999;

        $swiftSwiftException = new Swift_SwiftException($exceptionMsg, $exceptionCode);

        $this
            ->getMailer()
            ->method('send')
            ->willThrowException($swiftSwiftException);

        $checkResult = $this->getEmailSendCheck()->check();

        $this->assertFalse($checkResult->isOk());
        $this->assertEquals(EmailSendCheckException::CODE_INTERNAL_PROBLE, $checkResult->getError()->getCode());
        $this->assertStringEndsWith($exceptionMsg, $checkResult->getError()->getMessage());
        $this->assertInstanceOf(
            EmailSendCheckException::class,
            $checkResult->getError()
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 32453
     *
     * Test is fail with unexpected exception
     */
    public function testSendThrowUnexpectedException()
    {
        $this->setUpEntity();

        $exceptionMsg = 'Unexpected error msg text';
        $exceptionCode = 32453;

        $exception = new Exception($exceptionMsg, $exceptionCode);

        $this
            ->getMailer()
            ->method('send')
            ->willThrowException($exception);

        $this->getEmailSendCheck()->performCheck();
    }

    /**
     * @return EmailSendCheck
     */
    public function getEmailSendCheck()
    {
        return $this->emailSendCheck;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getMailer()
    {
        return $this->mailer;
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
     * @param EmailSendCheck $EmailSendCheck
     */
    protected function setEmailSendCheck(EmailSendCheck $emailSendCheck)
    {
        $this->emailSendCheck = $emailSendCheck;
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
     * set up entity.
     */
    private function setUpEntity()
    {
        $emailSendReceive = new EmailSendReceive();

        $emailSendReceive->setSentAt(new DateTime('-1 day'));

        $emailSendReceiveColl = new EmailSendReceiveCollection();
        $emailSendReceiveColl->add($emailSendReceive);

        $this->getPersistCollection()->persist($emailSendReceiveColl);
        $this->getPersistCollection()->flush();
    }

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
