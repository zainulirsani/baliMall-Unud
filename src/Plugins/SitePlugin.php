<?php

namespace App\Plugins;

class SitePlugin extends BasePlugin
{
    public function showcase()
    {
        return $this->view('@__main__/plugins/site/showcase.html.twig', [], 'html');
    }
}
