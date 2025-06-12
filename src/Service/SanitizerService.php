<?php
namespace App\Service;

use HTMLPurifier;
use HTMLPurifier_Config;

class SanitizerService
{
    private $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,strong,i,em,u,a[href],ul,ol,li,br,span,img[src|alt|width|height],blockquote');
        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(string $dirtyHtml): string
    {
        return $this->purifier->purify($dirtyHtml);
    }
}