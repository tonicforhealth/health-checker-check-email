<?php

namespace TonicHealthCheck\Check\Email\Receive;

use TonicHealthCheck\Check\Email\EmailCheckException;

/**
 * Class EmailReceiveCheckException
 * @package TonicHealthCheck\Check\Email\Receive
 */
class EmailReceiveCheckException extends EmailCheckException
{
    const EXCEPTION_NAME = 'EmailReceiveCheck';

    const CODE_RECEIVING_MAX_TIME_EXPIRE = 8003;
    const TEXT_RECEIVING_MAX_TIME_EXPIRE = 'Email:%s receiving max time expire. Time left:%dsec. max:%dsec.';

    /**
     * @param string $subject
     * @param int    $leftTime
     * @param int    $maxTime
     * @return EmailReceiveCheckException
     */
    public static function receivingMaxTimeExpire($subject, $leftTime, $maxTime)
    {
        return new static(sprintf(static::TEXT_RECEIVING_MAX_TIME_EXPIRE, $subject, $leftTime, $maxTime), static::CODE_RECEIVING_MAX_TIME_EXPIRE);
    }
}
