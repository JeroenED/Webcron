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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class JobController extends AbstractController
{
    #[Route('/{_locale}/job', name: 'job_index')]
    public function defaultAction(ManagerRegistry $doctrine): Response
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $jobs = $jobRepo->getAllJobs();
        return $this->render('job/index.html.twig', ['jobs' => $jobs]);
    }

    #[Route('/{_locale}/job/{id}/{all}', name: 'job_view', methods: ['GET'], defaults: [ 'all' => false ], requirements: ['id' => '\d+', 'all' => '(all|)'])]
    public function viewAction(Request $request, ManagerRegistry $doctrine, int $id, mixed $all = false): Response
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $runRepo = $doctrine->getRepository(Run::class);

        $job = $jobRepo->find($id);
        $runs = $runRepo->getRunsForJob($job, $all != 'all');
        return $this->render('job/view.html.twig', ['job' => $job, 'runs' => $runs, 'allruns' => $all == 'all']);
    }

    #[Route('/{_locale}/job/{id}', name: 'job_delete', methods: ['DELETE'], defaults: [ 'all' => false ], requirements: ['id' => '\d+', 'all' => '(all|)'])]
    public function deleteAction(Request $request, ManagerRegistry $doctrine, int $id, mixed $all = false): Response
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $success = $jobRepo->deleteJob($id);
        $this->addFlash('success', 'job.index.flashes.jobdeleted');
        return new JsonResponse(['return_path' => $this->GenerateUrl('job_index')]);
    }

    #[Route('/{_locale}/job/{id}/edit', name: 'job_edit', requirements: ['id' => '\d+'])]
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
            $this->addFlash('success', 'job.edit.flashes.jobedited');
            return new RedirectResponse($this->GenerateUrl('job_index'));
        }
        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/{_locale}/job/add', name: 'job_add')]
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
            $this->addFlash('success', 'job.add.flashes.jobadded');
            return new RedirectResponse($this->GenerateUrl('job_index'));
        } else {
            return new Response('Not implemented yet', Response::HTTP_TOO_EARLY);
        }
    }
    #[Route('/{_locale}/job/{id}/run/{timestamp}', name: 'job_run', defaults: ['timestamp' => 0 ], requirements: ['id' => '\d+', 'timestamp' => '\d+'])]
    public function runAction(Request $request, ManagerRegistry $doctrine, TranslatorInterface $translator, int $id, int $timestamp): JsonResponse
    {
        if($request->getMethod() == 'GET') {
            $jobRepo = $doctrine->getRepository(Job::class);
            $job = $jobRepo->find($id);
            $runResult = $jobRepo->run($job, false, $timestamp);
            if ($runResult['success'] === NULL) {
                $return = [
                    'status' => 'deferred',
                    'success' => NULL,
                    'title' => $translator->trans('job.index.run.deferred.title'),
                    'message' => $translator->trans('job.index.run.deferred.message')
                ];
            } else {
                $return = [
                    'status' => 'ran',
                    'success' => $runResult['success'],
                    'title' => $runResult['success'] ? $translator->trans('job.index.run.ran.title.success') : $translator->trans('job.index.run.ran.title.failed'),
                    'message' => $translator->trans('job.index.run.ran.message', [
                        '_runtime_' => number_format($runResult['runtime'], 3),
                        '_exitcode_' => $runResult['exitcode']
                    ]),
                    'exitcode' => $runResult['exitcode'],
                    'output' => $runResult['output'],
                ];
            }
            return new JsonResponse($return);
        }
        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/hook/{id}/{token}', name: 'webhook', requirements: ['id' => '\d+', 'token' => '[A-Za-z0-9]+'])]
    public function hookAction(Request $request, ManagerRegistry $doctrine, int $id, string $token)
    {
        $jobRepo = $doctrine->getRepository(Job::class);
        $job = $jobRepo->find($id);
        if(!empty($job->getToken()) && $job->getToken() == $token && $job->getRunning() != 1) {
            $jobRepo->setTempVar($job, 'webhook', true);
            return new JsonResponse($jobRepo->run($job, false, time()));
        }

        return new JsonResponse(['success'=>false, 'message' => 'Your request is invalid'], Response::HTTP_BAD_REQUEST);
    }
}