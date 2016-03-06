<?php

namespace TonicHealthCheck\Tests\Check\Email\SendReceive;

use DateTime;
use diversen\cache;
use Doctrine\ORM\EntityManager;
use Exception;
use PhpImap\Mailbox;
use PHPUnit_Framework_MockObject_MockObject;
use PHPUnit_Framework_TestCase;
use Swift_Mailer;
use Swift_Mime_Message;
use Swift_SmtpTransport;
use Swift_SwiftException;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveRepository;
use TonicHealthCheck\Check\Email\SendReceive\EmailReceiveCheckException;
use TonicHealthCheck\Check\Email\SendReceive\EmailSendCheckException;
use TonicHealthCheck\Check\Email\SendReceive\EmailSendReceiveCheck;
use PhpImap\Exception as ImapException;

/**
 * Class EmailSendReceiveCheckTest
 * @package TonicHealthCheck\Tests\Elasticsearch\GetDocument
 */
class EmailSendReceiveCheckTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var EmailSendReceiveCheck;
     */
    private $emailSendReceiveCheck;

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
    private $mailbox;

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

        $this->setMailbox($this->getMockBuilder(Mailbox::class)->disableOriginalConstructor()->getMock());

        $this->setDoctrine($this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock());

        $this->setEmailSendReceiveCheck(new EmailSendReceiveCheck(
            'testnode',
            $this->getMailer(),
            $this->getMailbox(),
            $this->getDoctrine(),
            'test@test.com',
            'to_test@test.com',
            '300'
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

        $this->setUpGetMailBoxMock();

        $checkResult = $this->getEmailSendReceiveCheck()->performCheck();

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

        $checkResult = $this->getEmailSendReceiveCheck()->performCheck();

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

        $checkResult = $this->getEmailSendReceiveCheck()->performCheck();

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

        $this->getEmailSendReceiveCheck()->performCheck();
    }

    /**
     * Test is fail with exception
     */
    public function testSearchMailboxThrowException()
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

        $checkResult = $this->getEmailSendReceiveCheck()->performCheck();

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

        $this->setUpSendMock();

        $exceptionMsg = 'Unexpected error msg text';
        $exceptionCode = 32453;

        $exception = new Exception($exceptionMsg, $exceptionCode);

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willThrowException($exception);

        $this->getEmailSendReceiveCheck()->performCheck();
    }

    /**
     * Test is fail with exception
     */
    public function testThrowReceivingMaxTimeExpireException()
    {

        $this->setUpEntity();

        $this->setUpSendMock();

        $exceptionMsg = 'Error msg text';
        $exceptionCode = 124999;

        $imapException = new ImapException($exceptionMsg, $exceptionCode);

        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willReturn([1,2,3,4,5]);

        $checkResultFirst = $this->getEmailSendReceiveCheck()->performCheck();

        $checkResultSecond = $this->getEmailSendReceiveCheck()->performCheck();

        $this->assertTrue($checkResultFirst->isOk());
        $this->assertFalse($checkResultSecond->isOk());
        $this->assertEquals(EmailReceiveCheckException::CODE_RECEIVING_MAX_TIME_EXPIRE, $checkResultSecond->getError()->getCode());
         $this->assertInstanceOf(
            EmailReceiveCheckException::class,
             $checkResultSecond->getError()
        );
    }

    /**
     * @return EmailSendReceiveCheck
     */
    public function getEmailSendReceiveCheck()
    {
        return $this->emailSendReceiveCheck;
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
     * @param EmailSendReceiveCheck $emailSendReceiveCheck
     */
    protected function setEmailSendReceiveCheck(EmailSendReceiveCheck $emailSendReceiveCheck)
    {
        $this->emailSendReceiveCheck = $emailSendReceiveCheck;
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

        $emailSendReceiveRepository->method('findOneBy')->willReturn(null);


        $emailSendReceive = new EmailSendReceive();

        $emailSendReceive->setSentAt(new DateTime('-1 day'));

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

    private function setUpGetMailBoxMock()
    {
        $this
            ->getMailbox()
            ->method('searchMailbox')
            ->willReturn([1, 2, 3, 4, 5]);
    }
}
