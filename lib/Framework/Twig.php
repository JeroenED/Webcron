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

        if($_ENV['DEBUG'] != 'true') {
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
        $secondsToInterval = new TwigFilter('interval', function(int|float $time) {
            $return = '';

            $days = floor($time / (60 * 60 * 24));
            $time -= $days * (60 * 60 * 24);
            $return .= ($days != 0) ? "{$days}d " : '';

            $hours = floor($time / (60 * 60));
            $time -= $hours * (60 * 60);
            $return .= ($hours != 0) ? "{$hours}h " : '';

            $minutes = floor($time / 60);
            $time -= $minutes * 60;
            $return .= ($minutes != 0) ? "{$minutes}m " : '';

            $time = round($time, 3);
            $return .= ($time != 0) ? "{$time}s " : '';

            return $return;
        });
        $parseTags = new TwigFilter('parsetags', function(string $text) {
            $results = [];
            preg_match_all('/\[([A-Za-z0-9 \-]+)\]/', $text, $results);
            foreach ($results[0] as $key=>$result) {
                $background = substr(md5($results[0][$key]), 0, 6);
                $color = $this->lightOrDark($background) == 'dark' ? 'ffffff' : '000000';
                $text = str_replace($results[0][$key], '<span class="tag" style="background-color: #' . $background . '; color: #' . $color . '">' . $results[1][$key] . '</span>', $text);
            }
            return $text;
        });

        $this->environment->addFilter($secondsToInterval);
        $this->environment->addFilter($parseTags);

    }

    private function lightOrDark ($color) {
        $color = str_split($color, 2);
        foreach($color as &$value) {
            $value = hexdec($value);
        }

        // HSP (Highly Sensitive Poo) equation from http://alienryderflex.com/hsp.html
        $hsp = sqrt(
            0.299 * ($color[0] * $color[0]) +
            0.587 * ($color[1] * $color[1]) +
            0.114 * ($color[2] * $color[2])
        );


        // Using the HSP value, determine whether the color is light or dark
        if ($hsp>140) {
            return 'light';
        } else {
            return 'dark';
        }
    }
}