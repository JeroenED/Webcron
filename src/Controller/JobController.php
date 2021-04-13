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
        $jobRepo = new Job($this->getDbCon());
        $job = $jobRepo->getJob($id);
    }

    public function addAction()
    {
        if($this->getRequest()->getMethod() == 'GET') {
            return $this->render('job/add.html.twig');
        } elseif ($this->getRequest()->getMethod() == 'POST') {
            $allValues = $this->getRequest()->request->all();
            $jobRepo = new Job($this->getDbCon());
            $joboutput = $jobRepo->addJob($allValues);
            if($joboutput['success']) {
                $this->addFlash('success', $joboutput['message']);
                return new RedirectResponse($this->generateRoute('job_index'));
            } else {
                $this->addFlash('danger', $joboutput['message']);
                return new RedirectResponse($this->generateRoute('job_add'));
            }
        } else {
            return new Response('Not implemented yet', Response::HTTP_TOO_EARLY);
        }
    }
}