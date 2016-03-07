<?php

namespace TonicHealthCheck\Tests\Check\Email\Send;

use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Swift_Mailer;
use Swift_Mime_Message;
use Swift_SmtpTransport;
use Swift_SwiftException;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveRepository;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;
use TonicHealthCheck\Check\Email\Send\EmailSendCheckException;

/**
 * Class EmailSendCheckTest
 * @package TonicHealthCheck\Tests\Elasticsearch\GetDocument
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
     * @var PHPUnit_Framework_MockObject_MockObject;
     */
    private $doctrine;


    /**
     * set up
     */
    public function setUp()
    {
        $this->setTransport($this->getMockBuilder(Swift_SmtpTransport::class)->getMock());

        $this->setMailer($this->getMockBuilder(Swift_Mailer::class)
            ->disableOriginalConstructor()
            ->getMock()
        );

        $this
            ->getMailer()
            ->method('getTransport')
            ->willReturn($this->getTransport());

        $this->setDoctrine($this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock());

        $this->setEmailSendCheck(new EmailSendCheck(
            'testnode',
            $this->getMailer(),
            $this->getDoctrine(),
            'test@test.com',
            'to_test@test.com',
            600
        ));

        parent::setUp();
    }

    /**
     * Test is ok
     */
    public function testCheckIsOk()
    {
        $this->setUpEntity();

        $this->setUpSendMock();


        $checkResult = $this->getEmailSendCheck()->performCheck();

        $this->assertTrue($checkResult->isOk());
        $this->assertNull($checkResult->getError());
    }

    /**
     * Test is fail
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

        $checkResult = $this->getEmailSendCheck()->performCheck();

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
     * Test is fail with exception
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

        $checkResult = $this->getEmailSendCheck()->performCheck();

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
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getDoctrine()
    {
        return $this->doctrine;
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
     * @param PHPUnit_Framework_MockObject_MockObject $doctrine
     */
    protected function setDoctrine(PHPUnit_Framework_MockObject_MockObject $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    private function setUpEntity()
    {
        $emailSendReceiveRepository = $this
            ->getMockBuilder(EmailSendReceiveRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getDoctrine()->method('getRepository')->willReturn($emailSendReceiveRepository);

        $emailSendReceive = new EmailSendReceive();

        $emailSendReceive->setSentAt(new DateTime('-1 day'));

        $emailSendReceiveRepository->method('findOneBy')->willReturn($emailSendReceive);

        $emailSendReceiveRepository->method('findBy')->willReturn([$emailSendReceive]);

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
