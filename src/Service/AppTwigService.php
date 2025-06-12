<?php

namespace App\Service;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class AppTwigService extends Environment
{
    public function __construct(KernelInterface $kernel)
    {
        parent::__construct(new FilesystemLoader('templates', $kernel->getProjectDir()));

        $this->addCustomFunctions();
    }

    private function addCustomFunctions(): void
    {
        $this->addFunction(new TwigFunction('site_url', function ($uri) {
            $protocol = getenv('APP_COOKIE_SECURE') === 'yes' ? 'https' : 'http';
            $baseurl = $protocol.'://'.getenv('APP_URL');

            return !empty($uri) ? $baseurl.'/'.$uri : $baseurl;
        }));
    }
}
