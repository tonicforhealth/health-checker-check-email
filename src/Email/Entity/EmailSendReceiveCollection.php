<?php

namespace TonicHealthCheck\Check\Email\Entity;

use Collections\Collection;

/**
 * Class EmailSendReceiveCollection
 * @package TonicHealthCheck\Check\Email\Entity
 */
class EmailSendReceiveCollection extends Collection
{
    const OBJECT_CLASS = EmailSendReceive::class;

    /**
     * ProcessingCheckCollection constructor.
     */
    public function __construct()
    {
        parent::__construct(static::OBJECT_CLASS);
    }
}
