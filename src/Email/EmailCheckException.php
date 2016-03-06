<?php

namespace TonicHealthCheck\Check\Email;

use TonicHealthCheck\Check\CheckException;

/**
 * Class EmailCheckException
 * @package TonicHealthCheck\Check\Elasticsearch
 */
class EmailCheckException extends CheckException
{
    const EXCEPTION_NAME = 'EmailCheck';

    const CODE_INTERNAL_PROBLE = 8001;
    const TEXT_INTERNAL_PROBLE = 'Email send internal problem: %s';

    /**
     * @param \Exception $e
     * @return static
     */
    public static function internalProblem(\Exception $e)
    {
        return new static(sprintf(static::TEXT_INTERNAL_PROBLE, $e->getMessage()), static::CODE_INTERNAL_PROBLE, $e);
    }
}
