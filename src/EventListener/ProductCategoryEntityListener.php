<?php

namespace App\EventListener;

use App\Entity\ProductCategory;
use Doctrine\Persistence\ObjectManager;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\SftpUploader;

class ProductCategoryEntityListener implements ListenerInterface
{
    protected $sftpUploader;

    public function __construct()
    {
        $this->sftpUploader = new SftpUploader();
    }

    public function handle(GenericEvent $event): void
    {
        $productCategory = $event->getSubject();

        if ($productCategory instanceof ProductCategory && $event->hasArgument('em')) {
            // dd('masuk1');
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(ProductCategory::class, 6, $alphabet);
            $dirSlug = $encoder->encode($productCategory->getId());
            /** @var ProductCategory $duplicate */
            $duplicate = $em->getRepository(ProductCategory::class)->findOneBy(['dirSlug' => $dirSlug]);

            if ($duplicate instanceof ProductCategory) {
                $salt ='App\Entity\DuplicateProductCategory-'.date('YmdHis');
                $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                $dirSlug = $duplicateEncoder->encode($productCategory->getId());
            }

            $productCategory->setDirSlug($dirSlug);
            
            $fs = new Filesystem();
            $images = ['desktop', 'mobile'];

            foreach ($images as $image) {
                $getter = sprintf('get%sImage', ucfirst($image));
                $setter = sprintf('set%sImage', ucfirst($image));
                $imageValue = $productCategory->{$getter}();

                if (!empty($imageValue) && $imageValue !== 'dist/img/no-image.png') {
                    $parts = explode('/', $imageValue);
                    $fileName = end($parts);
                    $publicPath = getenv('APP_PUBLIC_PATH');
                    $newPath = 'uploads/product_categories/'.$dirSlug.'/'.$fileName;

                    $originFile = __DIR__.'/../../'.$publicPath.'/'.$imageValue;

                    $localFilePath  = $imageValue;
                    $remoteDir      = "uploads/product_categories/".$dirSlug . '/';
                    $remoteFilePath = $fileName;

                    if ($fs->exists($originFile)) {
                        $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
                        $fs->remove($originFile);

                        $productCategory->{$setter}($newPath);
                    }
                }
            }

            $em->persist($productCategory);
            $em->flush();
        }
    }
}
