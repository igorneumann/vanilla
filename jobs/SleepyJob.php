<?php

/**
 * @license Proprietary
 * @copyright 2009-2018 Vanilla Forums Inc.
 */

namespace Vanilla\Job;

use Garden\QueueInterop\AbstractJob;
use Psr\Log\LogLevel;

/**
 * Vanilla Test Job: SleepyJob
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 */
class SleepyJob extends AbstractJob {

    /**
     * Run job
     *
     * @throws \Exception
     */
    public function run() {

        $this->log(LogLevel::NOTICE, "Sleepy job running");

        $min = $this->get('min', 200);
        $max = $this->get('max', 2200);

        if (!is_int($min) && $min < 0) {
            $min = 0;
        }

        if (!is_int($max) && $max > 5000) {
            $max = 5000;
        }

        if ($max < $min) {
            $max = $min;
        }

        $ms = mt_rand($min, $max);
        usleep($ms*1000);

        $this->log(LogLevel::NOTICE, "  sleeping for {ms}ms", [
            'ms' => $ms
        ]);

    }

}
