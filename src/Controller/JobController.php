<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use JeroenED\Webcron\Repository\Job;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    public function jobAction($id)
    {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }
        $jobRepo = new Job($this->getDbCon());

        if($this->getRequest()->getMethod() == 'GET') {
            $job = $jobRepo->getJob($id);
            return new Response('Not implemented, yet', Response::HTTP_NOT_IMPLEMENTED);
        } elseif($this->getRequest()->getMethod() == 'DELETE') {
            $success = $jobRepo->deleteJob($id);
            $this->addFlash('success', $success['message']);
            return new JsonResponse(['return_path' => $this->generateRoute('job_index')]);
        }
    }

    public function editAction($id)
    {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }
        if($this->getRequest()->getMethod() == 'GET') {
            $jobRepo = new Job($this->getDbCon());
            $job = $jobRepo->getJob($id, true);
            return $this->render('job/edit.html.twig', $job);
        } elseif($this->getRequest()->getMethod() == 'POST') {
            $allValues = $this->getRequest()->request->all();
            $jobRepo = new Job($this->getDbCon());

            try {
                $joboutput = $jobRepo->editJob($id, $allValues);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
                return new RedirectResponse($this->generateRoute('job_edit', ['id' => $allValues['id']]));
            }
            $this->addFlash('success', $joboutput['message']);
            return new RedirectResponse($this->generateRoute('job_index'));
        }
    }

    public function addAction()
    {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }

        if($this->getRequest()->getMethod() == 'GET') {
            return $this->render('job/add.html.twig');
        } elseif ($this->getRequest()->getMethod() == 'POST') {
            $allValues = $this->getRequest()->request->all();
            $jobRepo = new Job($this->getDbCon());
            try {
                $joboutput = $jobRepo->addJob($allValues);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
                return new RedirectResponse($this->generateRoute('job_add'));
            }
            $this->addFlash('success', $joboutput['message']);
            return new RedirectResponse($this->generateRoute('job_index'));

        } else {
            return new Response('Not implemented yet', Response::HTTP_TOO_EARLY);
        }
    }
}