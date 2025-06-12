<?php

namespace App\EventListener;

use App\Entity\Product;
use App\Entity\ProductFile;
use Doctrine\Persistence\ObjectManager;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\SftpUploader;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ProductEntityListener implements ListenerInterface
{
    protected $sftpUploader;

    public function __construct()
    {
        $this->sftpUploader = new SftpUploader();
    }

    public function handle(GenericEvent $event): void
    {
        $product = $event->getSubject();

        if ($product instanceof Product && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(Product::class, 6, $alphabet);
            $dirSlug = $encoder->encode($product->getId());
            /** @var Product $duplicate */
            $duplicate = $em->getRepository(Product::class)->findOneBy(['dirSlug' => $dirSlug]);

            if ($duplicate instanceof Product) {
                $salt ='App\Entity\DuplicateProduct-'.date('YmdHis');
                $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                $dirSlug = $duplicateEncoder->encode($product->getId());
            }

            $product->setDirSlug($dirSlug);

            $is_last = false;

            if ($event->hasArgument('is_last')) {
                $is_last = $event->getArgument('is_last');
            }

            if ($event->hasArgument('images')) {
                $images = $event->getArgument('images');

                if (count($images) > 0) {
                    $fs = new Filesystem();

                    foreach ($images as $image) {
                        /** @var ProductFile $image */
                        $parts = explode('/', $image->getFilePath());
                        $imageName = end($parts);
                        $publicPath = getenv('APP_PUBLIC_PATH');
                        $newPath = 'uploads/products/'.$dirSlug.'/'.$imageName;
                        
                        $originFile = __DIR__.'/../../'.$publicPath.'/'.$image->getFilePath();
						
                        $localFilePath  = $image->getFilePath();
                        $remoteDir      = "uploads/products/". $dirSlug .'/';
                        $remoteFilePath = $imageName; 
                        $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
						
						$image->setFilePath($newPath);

                        $em->persist($image);
							
                        if ($fs->exists($originFile)) {
                            if ($is_last) {
                                $fs->remove($originFile);
                            }
                        }
                    }
                }
            }

            $em->persist($product);
            $em->flush();
        }
    }
}
