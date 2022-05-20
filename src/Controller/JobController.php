<?php


namespace App\Controller;

use App\Entity\Job;
use App\Entity\Run;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class JobController extends AbstractController
{
    public function defaultAction(ManagerRegistry $doctrine): Response
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $jobs = $jobRepo->getAllJobs();
        return $this->render('job/index.html.twig', ['jobs' => $jobs]);
    }

    public function jobAction(Request $request, ManagerRegistry $doctrine, int $id, mixed $all = false): Response
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $runRepo = $doctrine->getRepository(Run::class);

        if($request->getMethod() == 'GET') {
            $job = $jobRepo->find($id);
            $runs = $runRepo->getRunsForJob($job, $all != 'all');
            return $this->render('job/view.html.twig', ['job' => $job, 'runs' => $runs, 'allruns' => $all == 'all']);
        } elseif($request->getMethod() == 'DELETE') {
            $success = $jobRepo->deleteJob($id);
            $this->addFlash('success', $success['message']);
            return new JsonResponse(['return_path' => $this->GenerateUrl('job_index')]);
        }
        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }

    public function editAction(Request $request, ManagerRegistry $doctrine, int $id): Response
    {
        if($request->getMethod() == 'GET') {
            $jobRepo = $doctrine->getRepository(Job::class);
            $job = $jobRepo->find($id);
            return $this->render('job/edit.html.twig', ['job' => $job]);
        } elseif($request->getMethod() == 'POST') {
            $allValues = $request->request->all();
            $jobRepo = $doctrine->getRepository(Job::class);

            try {
                $joboutput = $jobRepo->editJob($id, $allValues);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
                return new RedirectResponse($this->GenerateUrl('job_edit', ['id' => $allValues['id']]));
            }
            $this->addFlash('success', $joboutput['message']);
            return new RedirectResponse($this->GenerateUrl('job_index'));
        }
        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }

    public function addAction(Request $request, ManagerRegistry $doctrine): Response
    {
        if($request->getMethod() == 'GET') {
            return $this->render('job/add.html.twig', ['data' => []]);
        } elseif ($request->getMethod() == 'POST') {
            $allValues = $request->request->all();
            $jobRepo = $doctrine->getRepository(Job::class);
            try {
                $joboutput = $jobRepo->addJob($allValues);
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', $e->getMessage());
                return new RedirectResponse($this->GenerateUrl('job_add'));
            }
            $this->addFlash('success', $joboutput['message']);
            return new RedirectResponse($this->GenerateUrl('job_index'));
        } else {
            return new Response('Not implemented yet', Response::HTTP_TOO_EARLY);
        }
    }

    public function runNowAction(Request $request, ManagerRegistry $doctrine, int $id): JsonResponse
    {
        if($request->getMethod() == 'GET') {
            $jobRepo = $doctrine->getRepository(Job::class);
            $job = $jobRepo->find($id);
            return new JsonResponse($jobRepo->runNow($job));
        }
        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }
}