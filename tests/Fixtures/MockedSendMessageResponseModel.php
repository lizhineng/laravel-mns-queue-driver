<?php

namespace Zhineng\QueueMns\Tests\Fixtures;

use AliyunMNS\Responses\SendMessageResponse;

class MockedSendMessageResponseModel extends SendMessageResponse
{
    protected $statusCode = 201;
    protected $succeed = true;
    protected $messageBodyMD5 = 'C5DD56A39F5F7BB8B3337C6D11B6D8C7';
    protected $messageId = '5F290C926D472878-2-14D9529****-200000001';
}
