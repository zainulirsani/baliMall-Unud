<?php

namespace App\Plugins;

use App\Entity\Banner;

class PromoPlugin extends BasePlugin
{
    public function homepage()
    {
        $bannerRepository = $this->getRepository(Banner::class);
        $leftBanner = $bannerRepository->findByPosition('left');
        $rightBanner = $bannerRepository->findByPosition('right');
        $topbanner = $bannerRepository->findByPosition('top');

        return $this->view('@__main__/plugins/promo/homepage.html.twig', [
            'left_banner'  => $leftBanner,
            'right_banner' => $rightBanner,
        ], 'html');
    }
}
