<?php


namespace JeroenED\Framework;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class Twig
{
    private Environment $environment;
    private Kernel $kernel;
    public function __construct(Kernel $kernel)
    {

        $loader = new FilesystemLoader([$kernel->getTemplateDir()]);
        $this->environment = new Environment($loader);
        $this->kernel = $kernel;
        $this->addFunctions();
    }

    public function render(string $template, array $vars = []): string
    {
        return $this->environment->render($template, $vars);
    }

    public function addFunctions()
    {
        $path = new TwigFunction('path', function(string $route) {
            return $this->kernel->getRouter()->getUrlForRoute($route);
        });
        $this->environment->addFunction($path);
    }

}