<?php


namespace JeroenED\Framework;


use Mehrkanal\EncoreTwigExtension\Extensions\EntryFilesTwigExtension;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Twig\Cache\FilesystemCache;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
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

        if(!$_ENV['DEBUG']) {
            $cache = new FilesystemCache($kernel->getCacheDir() . '/twig');
            $this->environment->setCache($cache);
        }

        $this->kernel = $kernel;
        $this->addExtensions();
        $this->addFunctions();
        $this->addFilters();
    }

    public function render(string $template, array $vars = []): string
    {
        return $this->environment->render($template, $vars);
    }

    public function addExtensions()
    {
        $this->environment->addExtension(new IntlExtension());
        $this->environment->addExtension(new EntryFilesTwigExtension(new EntrypointLookup('./public/build/entrypoints.json')));
    }
    public function addFunctions()
    {
        $path = new TwigFunction('path', function(string $route, array $params = []) {
            return $this->kernel->getRouter()->getUrlForRoute($route, $params);
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