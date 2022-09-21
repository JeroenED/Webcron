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
        $lasttick = file_get_contents($pidFile);
        $return = ((int)$lasttick >= (time() - 30));
        if (!$return) unlink($pidFile);
        return $return;
    }
}