<?php

namespace TonicHealthCheck\Check\Email\SendReceive;

use TonicHealthCheck\Check\Email\EmailCheckException;

/**
 * Class EmailSendCheckException
 * @package TonicHealthCheck\Check\Email\SendReceive
 */
class EmailSendCheckException extends EmailCheckException
{
    const EXCEPTION_NAME = 'EmailSendCheck';

    const CODE_DOES_NOT_SEND = 8002;
    const TEXT_DOES_NOT_SEND = 'Does not send email to:%s';

    /**
     * @param array $subjects
     * @return EmailSendCheckException
     */
    public static function doesNotSendMessage($subjects)
    {
        return new static(sprintf(static::TEXT_DOES_NOT_SEND, implode(',', $subjects)), static::CODE_DOES_NOT_SEND);
    }
}
