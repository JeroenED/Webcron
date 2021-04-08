<?php

namespace JeroenED\Framework;


use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    private $twig;
    private $request;
    private $database;
    private $kernel;

    public function __construct(Request $request, Kernel $kernel)
    {
        $this->twig = new Twig($kernel);
        $this->request = $request;
        $this->kernel = $kernel;
    }

    public function getDbCon(): Connection
    {
        return $this->kernel->getDbCon();
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @param string $template
     * @param array $vars
     * @return Response
     */
    public function render(string $template, array $vars = []): Response
    {
        $response = new Response($this->twig->render($template, $vars));
        return $response;
    }

    public function generateRoute(string $route): string
    {
        return $this->kernel->getRouter()->getUrlForRoute($route);
    }
}