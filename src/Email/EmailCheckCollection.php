<?php
namespace TonicHealthCheck\Check\Email;

use TonicHealthCheck\Check\AbstractCheckCollection;

/**
 * Class EmailCheckCollection
 * @package TonicHealthCheck\Check\Elasticsearch
 */
class EmailCheckCollection extends AbstractCheckCollection
{
    const OBJECT_CLASS = AbstractEmailCheck::class;
}
