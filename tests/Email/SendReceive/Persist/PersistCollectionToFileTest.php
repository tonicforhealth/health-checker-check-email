<?php

namespace TonicHealthCheck\Tests\Check\Email\Persist;

use PHPUnit_Framework_TestCase;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceiveCollection;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionToFile;


/**
 * Class PersistCollectionToFileTest
 */
class PersistCollectionToFileTest extends PHPUnit_Framework_TestCase
{

    /**
     * test file for save not writable
     * @expectedException        \TonicHealthCheck\Check\Email\Persist\PersistCollectionToFileException
     */
    public function testConstructor()
    {
        new PersistCollectionToFile('/test1/test2/test3');
    }

    /**
     * test constructor with null
     */
    public function testConstructorWithNULL()
    {
        $testP = new PersistCollectionToFile();

        $this->assertStringStartsWith(sys_get_temp_dir(), $testP->getDataFilePath());

    }

    /**
     * test file for save not exist created empty collection
     */
    public function testLoadNotExistFile()
    {
        $testP = new PersistCollectionToFile(sys_get_temp_dir(), 'test333');
        $testP->delete();
        $emailSendReceiveC = $testP->load();

        $this->assertInstanceOf(
            EmailSendReceiveCollection::class,
            $emailSendReceiveC
        );

    }

    /**
     * test delete data file
     */
    public function testDelete()
    {
        $testP = new PersistCollectionToFile(sys_get_temp_dir(), 'test333');
        $testP->load();
        $testP->flush();

        $result = $testP->delete();

        $this->assertTrue($result);

        $result = $testP->delete();

        $this->assertFalse($result);

    }

    /**
     * test destruct flush entity
     */
    public function testDestructDoFlush()
    {
        $tmpDir = sys_get_temp_dir();

        $testP = new PersistCollectionToFile($tmpDir);
        $emailSendReceiveC = $testP->load();

        $emailSendReceive = new EmailSendReceive();

        $emailSendReceiveC->add($emailSendReceive);

        unset($testP);

        $testP = new PersistCollectionToFile($tmpDir);

        $emailSendReceiveCNew = $testP->load();

        $this->assertEquals($emailSendReceiveCNew->at(0), $emailSendReceiveC->at(0));
    }


}

