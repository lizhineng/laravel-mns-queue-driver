<?php

namespace Zhineng\QueueMns\Tests\Fixtures;

use AliyunMNS\Responses\ReceiveMessageResponse;

class MockedReceiveMessageResponseModel extends ReceiveMessageResponse
{
    protected $succeed = true;
    protected $statusCode = 200;
    protected $messageId = '5F290C926D472878-2-14D9529A8FA-200000001';
    protected $messageBodyMD5 = 'C5DD56A39F5F7BB8B3337C6D11B6D8C7';
    protected $messageBody = 'This is a test message';
    protected $enqueueTime = '1250700979248';
    protected $nextVisibleTime = '1250700799348';
    protected $firstDequeueTime = '1250700779318';
    protected $dequeueCount = '1';
    protected $priority = '8';
    protected $receiptHandle = '1-ODU4OTkzNDU5My0xNDMyNzI3ODI3LTItOA==';
}
