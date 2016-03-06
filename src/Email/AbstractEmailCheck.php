<?php

namespace TonicHealthCheck\Check\Email;

use TonicHealthCheck\Check\AbstractCheck;

/**
 * Class AbstractEmailCheck
 * @package TonicHealthCheck\Check\Elasticsearch
 */
abstract class AbstractEmailCheck extends AbstractCheck
{
    const COMPONENT = 'email';
    const GROUP     = 'web';
}