<?php


namespace JeroenED\Webcron\Controller;

use JeroenED\Framework\Controller;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends Controller
{
    public function loginAction(): Response
    {
        return $this->render('security/login.html.twig');
    }
}