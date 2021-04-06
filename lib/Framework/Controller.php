<?php

namespace JeroenED\Framework;


use http\Env\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    private $twig;
    private $request;

    public function __construct(Request $request, Kernel $kernel)
    {
        $loader = new FilesystemLoader([$kernel->getTemplateDir()]);
        $this->twig = new Environment($loader);
        $this->request = $request;
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
}