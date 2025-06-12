<?php

namespace App\Plugins;

use App\Entity\Banner;

class BannerPlugin extends BasePlugin
{
    public function homepage(string $region = 'top')
    {
        $bannerRepository = $this->getRepository(Banner::class);
        $topBanner = $bannerRepository->findOneBy(['position' => 'top', 'status' => 'active']);

        $data = [
            'region' => $region,
            'banner' => $topBanner
        ];

        return $this->view('@__main__/plugins/banner/homepage.html.twig', $data, 'html');
    }

    public function homepageNew(string $section = 'desktop')
    {
        $data = [
            'class' => $section === 'mobile' ? 'dc9 tc12 block-mobile' : 'dc9 tc12 desktop-only',
        ];

        return $this->view('@__main__/plugins/banner/homepage_new.html.twig', $data, 'html');
    }
}
