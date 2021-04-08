<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use JeroenED\Webcron\Repository\Job;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function DefaultAction() {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }
        $jobRepo = new Job($this->getDbCon());
        $jobs = $jobRepo->getAllJobs();
        return $this->render('job/overview.html.twig', ['jobs' => $jobs]);
    }
}