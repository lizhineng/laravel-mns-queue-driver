<?php

namespace Zhineng\QueueMns\Tests;

use AliyunMNS\Client;
use AliyunMNS\Exception\MessageNotExistException;
use AliyunMNS\Model\QueueAttributes;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zhineng\QueueMns\MnsJob;
use Zhineng\QueueMns\MnsQueue;
use Zhineng\QueueMns\Tests\Fixtures\ReceiveMessageResponseModel;
use Zhineng\QueueMns\Tests\Fixtures\SendMessageResponseModel;

class MnsQueueTest extends TestCase
{
    protected function setUp(): void
    {
        $this->mns = m::mock(Client::class);

        $this->queueName = 'emails';

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData]);
        $this->mockedDelay = 10;
        $this->mockedMessageId = '5F290C926D472878-2-14D9529A8FA-200000001';
        $this->mockedReceiptHandle = '1-ODU4OTkzNDU5My0xNDMyNzI3ODI3LTItOA==';

        $this->mockedSendMessageResponseModel = SendMessageResponseModel::mock([
            'messageId' => $this->mockedMessageId,
            'messageBodyMD5' => md5($this->mockedPayload),
        ]);

        $this->mockedReceiveMessageResponseModel = ReceiveMessageResponseModel::mock([
            'messageBody' => $this->mockedPayload,
            'messageBodyMD5' => md5($this->mockedPayload),
            'receiptHandle' => $this->mockedReceiptHandle,
            'messageId' => $this->mockedMessageId,
        ]);

        $this->mockedQueueAttributesModel = new QueueAttributes(
            '30',
            '65536',
            '65536',
            '60',
            '0',
            $this->queueName,
            '1250700999',
            '1250700999',
            '20',
            '0',
            '0',
            true
        );
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testPopProperlyPopsJobOffOfMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer(m::mock(Container::class));
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('receiveMessage')->once()->andReturn($this->mockedReceiveMessageResponseModel);
        $result = $queue->pop($this->queueName);
        $this->assertInstanceOf(MnsJob::class, $result);
    }

    public function testPopProperlyHandlesEmptyMessage()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('receiveMessage')->once()->andThrow(MessageNotExistException::class, 404);
        $result = $queue->pop($this->queueName);
        $this->assertNull($result);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoMns()
    {
        $now = Carbon::now();
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($now->addSeconds(5), $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testDelayedPushProperlyPushesJobOntoMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->later($this->mockedDelay, $this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testPushProperlyPushesJobOntoMns()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->setContainer($container = m::spy(Container::class));
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('sendMessage')->once()->andReturn($this->mockedSendMessageResponseModel);
        $id = $queue->push($this->mockedJob, $this->mockedData, $this->queueName);
        $this->assertEquals($this->mockedMessageId, $id);
        $container->shouldHaveReceived('bound')->with('events')->once();
    }

    public function testSizeProperlyReadsMnsQueueSize()
    {
        $queue = new MnsQueue($this->mns, $this->queueName);
        $queue->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('getAttribute->getQueueAttributes')->once()->andReturn($this->mockedQueueAttributesModel);
        $size = $queue->size($this->queueName);
        $this->assertEquals(20, $size);
    }
}
