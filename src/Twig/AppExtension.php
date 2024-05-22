<?php
namespace App\Twig;

use App\Service\Secret;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('interval', [$this, 'parseInterval']),
            new TwigFilter('parsetags', [$this, 'parseTags']),
            new TwigFilter('decryptsecret', [$this, 'decryptSecret']),
            new TwigFilter('contents', [$this, 'getContents']),
        ];
    }

    public function getTests(): array
    {
        return [
            new TwigTest('ondisk', [$this, 'onDisk'])
        ];
    }

    /**
     * Converts seconds to days, hours, minutes and seconds
     *
     * @param int|float $time
     * @return string
     */
    function parseInterval(int|float $time): string
    {
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

        return (!empty($return)) ? trim($return) : '0.000s';
    }

    /**
     * Converts [tag] to a HTML span element with background color based on the md5 hash of the tag and text color based on whether background color is light or dark
     * @param string $text
     * @return string
     */
    function parseTags(string $text): string
    {
        $results = [];
        preg_match_all('/\[([A-Za-z0-9 \-]+)\]/', $text, $results);
        foreach ($results[0] as $key=>$result) {
            $background = substr(hash('murmur3a', $results[0][$key]), 0, 6);
            $color = $this->isDark($background) ? 'ffffff' : '000000';
            $text = str_replace($results[0][$key], '<span class="tag" data-background-color="#' . $background . '" data-color="#' . $color . '">' . $results[1][$key] . '</span>', $text);
        }
        return $text;
    }

    /**
     * Returns true if the color is considered to be dark
     *
     * @param string $color
     * @return bool
     */
    private function isDark(string $color): bool
    {
        $color = str_split($color, 2);
        foreach($color as &$value) {
            $value = hexdec($value);
        }

        // HSP equation from http://alienryderflex.com/hsp.html
        $hsp = sqrt(
            0.299 * ($color[0] * $color[0]) +
            0.587 * ($color[1] * $color[1]) +
            0.114 * ($color[2] * $color[2])
        );


        // Using the HSP value, determine whether the color is light or dark
        return ($hsp<150);
    }

    /**
     * Returns decrypted cipher text
     *
     * @param string $text
     * @return string
     */
    function decryptSecret(string $text): string
    {
        return Secret::decrypt(base64_decode($text));
    }

    /**
     * Returns the content of a file
     *
     * @param string $file
     * @return string
     */
    function getContents(string $file): string
    {
        return file_get_contents($file);
    }

    /**
     * @param string $file
     * @return string
     */
    public function onDisk(string $file): string
    {
        return file_exists($file);
    }
}
