<?php

namespace TonicHealthCheck\Tests\Check\Email\SendReceive\Entity;

use DateTime;
use TonicHealthCheck\Check\Email\Entity\EmailSendReceive;

/**
 * Class EmailSendReceiveTest
 * @package TonicHealthCheck\Tests\Check\Email\SendReceive\Entity
 */
class EmailSendReceiveTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test doStuffOnPrePersist
     */
    public function testDoStuffOnPrePersist()
    {
        $emailSendReceive = new EmailSendReceive();

        $dateTime = new DateTime();

        $emailSendReceive->setReceivedAt($dateTime);

        $this->assertEquals($dateTime, $emailSendReceive->getReceivedAt());

        $this->assertEquals(EmailSendReceive::STATUS_CREATED, $emailSendReceive->getStatus());
    }
}

