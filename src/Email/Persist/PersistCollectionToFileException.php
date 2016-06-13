<?php

namespace TonicHealthCheck\Check\Email\Persist;

/**
 * Class PersistCollectionToFileException
 * @package TonicHealthCheck\Incident\Siren\NotificationType
 */
class PersistCollectionToFileException extends \Exception
{
    /**
     * @param string $dir
     * @return PersistCollectionToFileException
     */
    public static function dirForSaveDoesNotWritable($dir)
    {
        return new self(sprintf('directory for save %s doesn\'t writable', $dir));
    }
}
