<?php

namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use JeroenED\Webcron\Repository\Job;
use Symfony\Component\HttpFoundation\JsonResponse;

class SiteController extends Controller
{
    public function HealthAction()
    {
        global $kernel;
        $jobRepo = new Job($this->getDbCon());
        $return = [
            "DaemonRunning" => file_exists($kernel->getCacheDir() . '/daemon-running.lock'),
            "JobsTotal" => count($jobRepo->getAllJobs()),
            "JobsDue" => count($jobRepo->getJobsDue()),
            "JobsRunning" => count($jobRepo->getRunningJobs()),
            "JobsFailing" => count($jobRepo->getFailingJobs()),
        ];
        return new JsonResponse($return, $return['DaemonRunning'] ? 200 : 500);
    }
}