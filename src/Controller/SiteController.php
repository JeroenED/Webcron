<?php

namespace App\Controller;

use App\Entity\Job;
use App\Service\DaemonHelpers;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class SiteController extends AbstractController
{

    #[Route('/health', name: 'health')]
    public function healthAction(DaemonHelpers $daemonHelpers)
    {
        $return = $daemonHelpers->healthCheck();
        return $this->json($return, $return['DaemonRunning'] ? Response::HTTP_OK : Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/favicon.ico', name: 'favicon')]
    public function faviconAction(Request $request)
    {
        return new Response('', 200);
    }
}