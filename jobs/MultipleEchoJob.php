<?php

/**
 * @license Proprietary
 * @copyright 2009-2018 Vanilla Forums Inc.
 */

namespace Vanilla\Job;

use Exception;
use Garden\QueueInterop\AbstractJob;
use Garden\QueueInterop\SchedulerAwareInterface;
use Garden\QueueInterop\SchedulerInterface;
use Psr\Log\LogLevel;

/**
 * Vanilla Test Job: MultipleEchoJob
 *
 * Shows how a job can schedule other jobs from within its payload.
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 */
class MultipleEchoJob extends AbstractJob implements SchedulerAwareInterface {

    /**
     *
     * @var SchedulerInterface
     */
    private $scheduler;

    /**
     * Run job
     *
     * @throws Exception
     */
    public function run() {

        $this->log(LogLevel::NOTICE, "Multiple echo jobs running");

        $jobMessages = [
            'This is the first EchoJob',
            'This is the second EchoJob',
            'This is the last EchoJob',
        ];

        $i = 0;
        foreach ($jobMessages as $msg) {
            $i++;

            $this->log(LogLevel::NOTICE, "  Dispatching nested job {id}", [
                'id' => $i
            ]);

            $job = $this->scheduler->addJob(EchoJob::class, [
                'message' => $msg
            ]);

            if ($job) {
                $this->log(LogLevel::NOTICE, "  Job has been received by the scheduler (id: {id}, status: {status})", [
                    'id' => $job->getID(),
                    'status' => $job->getStatus()
                ]);
            } else {
                $this->log(LogLevel::WARNING, "  Job was not received properly by the scheduler");
            }
        }

        $this->log(LogLevel::NOTICE, "  All nested jobs have been dispatched");

    }

    /**
     * Set the scheduler
     *
     * @param SchedulerInterface $scheduler
     */
    public function setScheduler(SchedulerInterface $scheduler) {
        $this->scheduler = $scheduler;
    }

}
