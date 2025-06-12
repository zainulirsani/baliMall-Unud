<?php

namespace App\Controller\Site;

use App\Controller\PublicController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class SiteController extends PublicController
{

    /**
    * @Cache(expires="tomorrow", public=true)
    */
    public function index()
    {
        return $this->view('@__main__/public/site/index.html.twig', [
            'page_title' => 'title.page.app',
            'is_homepage' => true,
        ]);
    }
}
