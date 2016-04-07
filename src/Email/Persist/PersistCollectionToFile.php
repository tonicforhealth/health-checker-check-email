<?php

namespace TonicHealthCheck\Check\Email\Persist;

use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;

/**
 * Class PersistCollectionToFile
 */
class PersistCollectionToFile implements PersistCollectionInterface
{
    const DATA_FILE_NAME = 'email_send_receive_collection.data';

    /**
     * @var string;
     */
    private $savePath;

    /**
     * @var string;
     */
    private $saveFileName = self::DATA_FILE_NAME;

    /**
     * @var EmailSendReceiveCollection;
     */
    private $emailSendReceiveC;


    /**
     * PersistCollectionToFile constructor.
     * @param string      $savePath
     * @param null|string $saveFileName
     * @throws PersistCollectionToFileException
     */
    public function __construct($savePath = null, $saveFileName = null)
    {
        if (null === $savePath) {
            $savePath = sys_get_temp_dir();
        }
        $this->setSavePath($savePath);

        if (null !== $saveFileName) {
            $this->setSaveFileName($saveFileName);
        }
    }

    /**
     * PersistCollectionToFiledestruct.
     */
    public function __destruct()
    {
        $this->flush();
    }

    /**
     * @param EmailSendReceiveCollection $emailSendReceiveC
     */
    public function persist(EmailSendReceiveCollection $emailSendReceiveC)
    {
        $this->setEmailSendReceiveC($emailSendReceiveC);
    }

    /**
     * @return bool
     */
    public function flush()
    {
        file_put_contents(
            $this->getDataFilePath(),
            serialize($this->getEmailSendReceiveC())
        );
    }

    /**
     * @return null|EmailSendReceiveCollection
     */
    public function load()
    {


        if (is_readable($this->getDataFilePath())) {
            $emailSendReceiveC = unserialize(
                file_get_contents($this->getDataFilePath())
            );

            if ($emailSendReceiveC instanceof EmailSendReceiveCollection) {
                $this->persist($emailSendReceiveC);
            }
        }

        if (null === $this->getEmailSendReceiveC()) {
            $this->persist(new EmailSendReceiveCollection());
        }

        return $this->getEmailSendReceiveC();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if (is_writable($this->getDataFilePath())) {
            $returnFlag = unlink($this->getDataFilePath());
        } else {
            $returnFlag = false;
        }

        return $returnFlag;
    }



    /**
     * @return string
     */
    protected function getSavePath()
    {
        return $this->savePath;
    }

    /**
     * @param string $savePath
     * @throws PersistCollectionToFileException
     */
    protected function setSavePath($savePath)
    {
        if (!is_writable($savePath)) {
            throw PersistCollectionToFileException::dirForSaveDoesNotWritable($savePath);
        }
        $this->savePath = rtrim($savePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }

    /**
     * @return EmailSendReceiveCollection
     */
    protected function getEmailSendReceiveC()
    {
        return $this->emailSendReceiveC;
    }

    /**
     * @param EmailSendReceiveCollection $emailSendReceiveC
     */
    protected function setEmailSendReceiveC(EmailSendReceiveCollection $emailSendReceiveC)
    {
        $this->emailSendReceiveC = $emailSendReceiveC;
    }

    /**
     * @return string
     */
    public function getDataFilePath()
    {
        return $this->getSavePath().$this->getSaveFileName();
    }

    /**
     * @return string
     */
    protected function getSaveFileName()
    {
        return $this->saveFileName;
    }

    /**
     * @param string $saveFileName
     */
    protected function setSaveFileName($saveFileName)
    {
        $this->saveFileName = $saveFileName;
    }
}
