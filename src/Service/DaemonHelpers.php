<?php

namespace App\Service;

use App\Entity\Job;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class DaemonHelpers
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private ManagerRegistry $doctrine,
    ) {}

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

    public function healthCheck(): array
    {
        $em = $this->doctrine->getManager();
        $jobRepo = $em->getRepository(Job::class);
        $return = [
            "DaemonRunning" => DaemonHelpers::isProcessRunning($this->parameterBag->get('pidfile')),
            "JobsTotal" => count($jobRepo->getAllJobs()),
            "JobsDue" => count($jobRepo->getJobsDue()),
            "JobsRunning" => count($jobRepo->getRunningJobs()),
            "JobsFailing" => count($jobRepo->getFailingJobs()),
        ];
        return $return;
    }
}