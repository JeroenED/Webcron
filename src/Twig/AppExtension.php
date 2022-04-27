<?php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('interval', [$this, 'parseInterval']),
            new TwigFilter('parsetags', [$this, 'parseTags']),
        ];
    }

    function parseInterval(int|float $time) {
        $return = '';

        $days = floor($time / (60 * 60 * 24));
        $time -= $days * (60 * 60 * 24);
        $return .= ($days != 0 || !empty($return)) ? "{$days}d " : '';

        $hours = floor($time / (60 * 60));
        $time -= $hours * (60 * 60);
        $return .= ($hours != 0 || !empty($return)) ? "{$hours}h " : '';

        $minutes = floor($time / 60);
        $time -= $minutes * 60;
        $return .= ($minutes != 0 || !empty($return)) ? "{$minutes}m " : '';

        $time = round($time, 3);
        $return .= ($time != 0 || !empty($return)) ? "{$time}s " : '';

        return $return;
    }

    function parseTags(string $text) {
        $results = [];
        preg_match_all('/\[([A-Za-z0-9 \-]+)\]/', $text, $results);
        foreach ($results[0] as $key=>$result) {
            $background = substr(md5($results[0][$key]), 0, 6);
            $color = $this->lightOrDark($background) == 'dark' ? 'ffffff' : '000000';
            $text = str_replace($results[0][$key], '<span class="tag" data-background-color="#' . $background . '" data-color="#' . $color . '">' . $results[1][$key] . '</span>', $text);
        }
        return $text;
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
