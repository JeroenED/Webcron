<?php


namespace JeroenED\Framework;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
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
        $this->addFilters();
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

    public function addFilters() {
        $secondsToInterval = new TwigFilter('interval', function(int $time) {
            $days = floor($time / (60 * 60 * 24));
            $time -= $days * (60 * 60 * 24);

            $hours = floor($time / (60 * 60));
            $time -= $hours * (60 * 60);

            $minutes = floor($time / 60);
            $time -= $minutes * 60;

            $seconds = floor($time);
            $time -= $seconds;

            return "{$days}d {$hours}h {$minutes}m {$seconds}s";
        });
        $this->environment->addFilter($secondsToInterval);
    }



}