<?php

namespace Zhineng\QueueMns;

use AliyunMNS\Client;
use AliyunMNS\Responses\ReceiveMessageResponse;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;

class MnsJob extends Job implements JobContract
{
    public function __construct(
        Container $container,
        protected $mns,
        protected ReceiveMessageResponse $job,
        ?string $connectionName,
        string $queue
    )
    {
        $this->container = $container;
        $this->connectionName = $connectionName;
        $this->queue = $queue;
    }

    /**
     * @inheritDoc
     */
    public function attempts()
    {
        return (int) $this->job->getDequeueCount();
    }

    /**
     * @inheritDoc
     */
    public function getJobId()
    {
        return $this->job->getMessageId();
    }

    /**
     * @inheritDoc
     */
    public function getRawBody()
    {
        return $this->job->getMessageBody();
    }
}
