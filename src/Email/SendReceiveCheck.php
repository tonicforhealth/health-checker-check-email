<?php

namespace TonicHealthCheck\Check\Email;

use Psr\Log\LoggerInterface;
use TonicHealthCheck\Check\CheckInterface as HCCheckInterface;
use TonicHealthCheck\Check\CheckResult;
use TonicHealthCheck\Check\Email\Receive\EmailReceiveCheck;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;
use ZendDiagnostics\Check\CheckInterface;
use ZendDiagnostics\Result\Failure;
use ZendDiagnostics\Result\ResultInterface;
use ZendDiagnostics\Result\Success;

/**
 * Class SendReceiveCheck
 */
class SendReceiveCheck implements CheckInterface
{
    const EMAIL_SEND_CHECK_MSG = '%s:Email send check %s';
    const EMAIL_RECEIVE_CHECK_MSG = '%s:Email receive check %s';
    const CHECK_OK = 'OK';
    const CHECK_FAIL = 'FAIL';
    const ERROR_MSG = 'Error:%s';

    /**
     * @var LoggerInterface
     */
    private $healthCheckerLogger;

    /**
     * @var EmailSendCheck
     */
    private $emailSendCheck;

    /**
     * @var EmailReceiveCheck
     */
    private $emailReceiveCheck;

    /**
     * Init Dependency
     * @param LoggerInterface   $healthCheckerLogger
     * @param EmailSendCheck    $emailSendCheck
     * @param EmailReceiveCheck $emailReceiveCheck
     */
    public function __construct(
        LoggerInterface $healthCheckerLogger,
        EmailSendCheck $emailSendCheck,
        EmailReceiveCheck $emailReceiveCheck
    ) {
        $this->setHealthCheckerLogger($healthCheckerLogger);
        $this->setEmailSendCheck($emailSendCheck);
        $this->setEmailReceiveCheck($emailReceiveCheck);
    }

    /**
     * Check email send&receive
     *
     * @return ResultInterface
     */
    public function check()
    {
        $resultSend = $this->emailCheck($this->getEmailSendCheck(), self::EMAIL_SEND_CHECK_MSG);
        $resultReceive = $this->emailCheck($this->getEmailReceiveCheck(), self::EMAIL_RECEIVE_CHECK_MSG);

        if (! $resultSend->isOk()) {
            $result = new Failure($resultSend->getError()->getMessage(), $resultSend->getError());
        } elseif (! $resultReceive->isOk()) {
            $result = new Failure($resultReceive->getError()->getMessage(), $resultReceive->getError());
        } else {
            $result = new Success();
        }

        return $result;
    }

    /**
     * Return a label describing this test instance.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->getEmailSendCheck()->getIndent().' <-> '.$this->getEmailReceiveCheck()->getIndent();
    }

    /**
     * @return LoggerInterface
     */
    protected function getHealthCheckerLogger()
    {
        return $this->healthCheckerLogger;
    }

    /**
     * @return EmailReceiveCheck
     */
    protected function getEmailReceiveCheck()
    {
        return $this->emailReceiveCheck;
    }

    /**
     * @return EmailSendCheck
     */
    protected function getEmailSendCheck()
    {
        return $this->emailSendCheck;
    }

    /**
     * @param LoggerInterface $healthCheckerLogger
     */
    protected function setHealthCheckerLogger(LoggerInterface $healthCheckerLogger)
    {
        $this->healthCheckerLogger = $healthCheckerLogger;
    }

    /**
     * @param EmailReceiveCheck $emailReceiveCheck
     */
    protected function setEmailReceiveCheck(EmailReceiveCheck $emailReceiveCheck)
    {
        $this->emailReceiveCheck = $emailReceiveCheck;
    }

    /**
     * @param EmailSendCheck $emailSendCheck
     */
    protected function setEmailSendCheck(EmailSendCheck $emailSendCheck)
    {
        $this->emailSendCheck = $emailSendCheck;
    }

    /**
     * @param HCCheckInterface $emailCheck
     * @param string         $checkMsg
     *
     * @return CheckResult
     */
    private function emailCheck(HCCheckInterface $emailCheck, $checkMsg)
    {
        $receiveCheckResult = $emailCheck->performCheck();
        $checkIndent = $emailCheck->getIndent();
        if ($receiveCheckResult->isOk()) {
            $this->logSuccessCheck($checkMsg, $checkIndent, self::CHECK_OK);
        } else {
            $this->logFailCheck($checkMsg, $checkIndent, $receiveCheckResult->getError()->getMessage(), self::CHECK_FAIL);
        }

        return $receiveCheckResult;
    }

    /**
     * @param string $checkStatus
     * @param string $checkIndent
     *
     * @return array
     */
    private function getCheckTags($checkStatus, $checkIndent)
    {
        return [
            'check_status' => $checkStatus,
            'check_indent' => $checkIndent,
        ];
    }

    /**
     * @param string $checkMsg
     * @param string $checkIndent
     * @param string $checkStatus
     */
    private function logSuccessCheck($checkMsg, $checkIndent, $checkStatus = self::CHECK_OK)
    {
        $msg = sprintf(
            $checkMsg,
            $checkIndent,
            $checkStatus
        );

        $this->getHealthCheckerLogger()->info(
            $msg,
            $this->getCheckTags($checkStatus, $checkIndent)
        );
    }

    /**
     * @param string $checkMsg
     * @param string $checkIndent
     * @param string $errorMsg
     * @param string $checkStatus
     */
    private function logFailCheck($checkMsg, $checkIndent, $errorMsg, $checkStatus = self::CHECK_FAIL)
    {
        $msg = sprintf(
            $checkMsg,
            $checkIndent,
            $checkStatus
        );

        $msg .= ' '.sprintf(self::ERROR_MSG, $errorMsg);

        $this->getHealthCheckerLogger()->emergency(
            $msg,
            $this->getCheckTags($checkStatus, $checkIndent)
        );
    }
}
