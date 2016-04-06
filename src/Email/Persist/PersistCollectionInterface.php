<?php

namespace TonicHealthCheck\Check\Email\Persist;

use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;

/**
 * Interface PersistCollectionInterface
 */
interface PersistCollectionInterface
{
    /**
     * @param EmailSendReceiveCollection $emailSendReceiveC
     */
    public function persist(EmailSendReceiveCollection $emailSendReceiveC);

    /**
     * @return bool
     */
    public function flush();

    /**
     * @return EmailSendReceiveCollection
     */
    public function load();

    /**
     * @return bool
     */
    public function delete();
}