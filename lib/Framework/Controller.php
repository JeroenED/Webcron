<?php

namespace JeroenED\Framework;


use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class Controller
{
    private $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(['src/Resources/templates']);
        $this->twig = new Environment($loader);
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