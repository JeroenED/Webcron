<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function DefaultAction() {
        if(!isset($_SESSION['isAuthenticated']) || !$_SESSION['isAuthenticated']) {
            return new RedirectResponse($this->generateRoute('login'));
        }
        return new Response('Not yet implemented', 425);
    }
}