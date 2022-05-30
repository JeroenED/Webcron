<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\DaemonHelpers;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class SiteController extends AbstractController
{

    public function healthAction(Request $request, ManagerRegistry $doctrine, KernelInterface $kernel)
    {
        $em = $doctrine->getManager();
        $jobRepo = $em->getRepository(Job::class);
        $return = [
            "DaemonRunning" => DaemonHelpers::isProcessRunning($kernel->getCacheDir() . '/daemon-running.lock'),
            "JobsTotal" => count($jobRepo->getAllJobs()),
            "JobsDue" => count($jobRepo->getJobsDue()),
            "JobsRunning" => count($jobRepo->getRunningJobs()),
            "JobsFailing" => count($jobRepo->getFailingJobs()),
        ];
        return new JsonResponse($return, $return['DaemonRunning'] ? 200 : 500);
    }
}