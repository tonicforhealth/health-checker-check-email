# health-checker-check-email

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/badges/build.png?b=master)](https://scrutinizer-ci.com/g/tonicforhealth/health-checker-check-email/build-status/master)

This is a component for checking email send&receive from smtp service point to imap inbox point.

## Installation using [Composer](http://getcomposer.org/)
------------
```bash
$ composer require tonicforhealth/health-checker-check-email
```

## Requirements
------------

- PHP 5.5 or higher
- ext-imap

## Usage
------------
```php
<?php

use PhpImap\Mailbox;
use TonicHealthCheck\Check\Email\Persist\PersistCollectionToFile;
use TonicHealthCheck\Check\Email\Receive\EmailReceiveCheck;
use TonicHealthCheck\Check\Email\Send\EmailSendCheck;

$checkNode = 'testnode';

$persistCollectionToFile = new PersistCollectionToFile(sys_get_temp_dir());

$transport = Swift_SmtpTransport::newInstance();

$mailer = Swift_Mailer::newInstance($transport);

$mailbox = new Mailbox('{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX', 'username', 'password');

$receiveMaxTime = 300;

$sendInterval = 600;

$emailSendCheck = new EmailSendCheck(
    $checkNode,
    $mailer,
    $persistCollectionToFile,
    'from_test@test.com',
    'to_test@test.com',
    $sendInterval
);

$emailReceiveCheck = new EmailReceiveCheck(
    $checkNode,
    $mailbox,
    $persistCollectionToFile,
    $receiveMaxTime
);

while (true) {
    $resultSend = $emailSendCheck->performCheck();
    printf(
        "Send result is:%s\n",
        $resultSend->isOk() ? 'true' : sprintf('false error:%s', $resultSend->getError()->getMessage())
    );
    sleep(10);
    $resultReceive = $emailReceiveCheck->performCheck();

    printf(
        "Receive result is:%s\n",
        $resultReceive->isOk() ? 'true' : sprintf('false error:%s', $resultReceive->getError()->getMessage())
    );
    sleep(60);
}

```
