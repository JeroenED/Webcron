<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use JeroenED\Webcron\Repository\Job;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class JobController extends Controller
{
    public function DefaultAction()
    {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }
        $jobRepo = new Job($this->getDbCon());
        $jobs = $jobRepo->getAllJobs();
        return $this->render('job/index.html.twig', ['jobs' => $jobs]);
    }

    public function viewAction($id)
    {
        return new Response('Not implemented yet', Response::HTTP_TOO_EARLY);
    }
}