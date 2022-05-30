<?php

namespace App\Service;

class DaemonHelpers
{
    /**
     * https://stackoverflow.com/a/3111757
     *
     * Checks if process with pid in $pidFile is still running
     *
     * @param $pidFile
     * @return bool
     */
    public static function isProcessRunning($pidFile = '/var/run/myfile.pid') {
        if (!file_exists($pidFile) || !is_file($pidFile)) return false;
        $pid = file_get_contents($pidFile);
        $return = posix_kill((int)$pid, 0);
        if (!$return) unlink($pidFile);
        return $return;
    }
}