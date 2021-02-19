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
        protected Client $mns,
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
    public function release($delay = 0)
    {
        parent::release($delay);

        $this->mns
            ->getQueueRef($this->queue)
            ->changeMessageVisibility($this->job->getReceiptHandle(), $delay);
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        parent::delete();

        $this->mns
            ->getQueueRef($this->queue)
            ->deleteMessage($this->job->getReceiptHandle());
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

    /**
     * Get the underlying MNS client instance.
     *
     * @return \AliyunMNS\Client
     */
    public function getMns(): Client
    {
        return $this->mns;
    }

    /**
     * Get the underlying raw MNS job.
     *
     * @return \AliyunMNS\Responses\ReceiveMessageResponse
     */
    public function getMnsJob(): ReceiveMessageResponse
    {
        return $this->job;
    }
}
