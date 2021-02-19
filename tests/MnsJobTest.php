<?php

namespace Zhineng\QueueMns\Tests;

use AliyunMNS\Client;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zhineng\QueueMns\MnsJob;
use Zhineng\QueueMns\Tests\Fixtures\ReceiveMessageResponseModel;

class MnsJobTest extends TestCase
{
    protected function setUp(): void
    {
        $this->queueName = 'emails';
        $this->releaseDelay = 0;

        $this->mockedContainer = m::mock(Container::class);
        $this->mockedMnsClient = m::mock(Client::class);

        $this->mockedJob = 'foo';
        $this->mockedData = ['data'];
        $this->mockedPayload = json_encode(['job' => $this->mockedJob, 'data' => $this->mockedData, 'attempts' => 1]);
        $this->mockedMessageId = '5F290C926D472878-2-14D9529A8FA-200000001';
        $this->mockedReceiptHandle = '1-ODU4OTkzNDU5My0xNDMyNzI3ODI3LTItOA==';

        $this->mockedJobData = ReceiveMessageResponseModel::mock([
            'messageBody' => $this->mockedPayload,
            'messageBodyMD5' => md5($this->mockedPayload),
            'receiptHandle' => $this->mockedReceiptHandle,
            'messageId' => $this->mockedMessageId,
            'dequeueCount' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    public function testFireProperlyCallsTheJobHandler()
    {
        $job = $this->getJob();
        $job->getContainer()->shouldReceive('make')->once()->with('foo')->andReturn($handler = m::mock(stdClass::class));
        $handler->shouldReceive('fire')->once()->with($job, ['data']);
        $job->fire();
    }

    public function testDeleteRemovesTheJobFromMns()
    {
        $job = $this->getJob();
        $job->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('deleteMessage')->once()->with($this->mockedReceiptHandle);
        $job->delete();
    }

    public function testReleaseProperlyReleasesTheJobOntoMns()
    {
        $job = $this->getJob();
        $job->getMns()->shouldReceive('getQueueRef')->once()->with($this->queueName)->andReturn($queueRef = m::mock(stdClass::class));
        $queueRef->shouldReceive('changeMessageVisibility')->once()->with($this->mockedReceiptHandle, $this->releaseDelay);
        $job->release($this->releaseDelay);
        $this->assertTrue($job->isReleased());
    }

    protected function getJob(): MnsJob
    {
        return new MnsJob(
            $this->mockedContainer,
            $this->mockedMnsClient,
            $this->mockedJobData,
            'connection-name',
            $this->queueName
        );
    }
}