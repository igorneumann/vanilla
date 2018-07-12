<?php

/**
 * @license Proprietary
 * @copyright 2009-2018 Vanilla Forums Inc.
 */

namespace Vanilla\Job;

use Exception;
use Garden\Db\Db;
use Garden\QueueInterop\AbstractJob;
use Garden\QueueInterop\DatabaseAwareInterface;
use Psr\Log\LogLevel;

/**
 * Vanilla Test Job: DatabaseJob
 *
 * @author Eric Vachaviolos <eric.v@vanillaforums.com>
 */
class DatabaseJob extends AbstractJob implements DatabaseAwareInterface {

    /**
     *
     * @var Db
     */
    private $db;

    /**
     * Run job
     *
     * @throws Exception
     */
    public function run() {

        $this->log(LogLevel::NOTICE, "Database job running: " . get_class($this->db));


        $userID = 8;

        try {
            $stmt = $this->db->get('User', ['UserID' => $userID]);
        } catch (Exception $ex) {
            $this->log(LogLevel::NOTICE, "Exception received: " . $ex->getMessage());
            return;
        }

        $this->log(LogLevel::NOTICE, " user $userID = {Name}", $stmt->fetch());


    }

    public function setDatabase(Db $database) {
        $this->db = $database;
    }

}
