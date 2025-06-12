<?php

namespace App\EventListener;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Persistence\ObjectManager;
use Hashids\Hashids;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\SftpUploader;

class UserEntityListener implements ListenerInterface
{

    protected $sftpUploader;

    public function __construct()
    {
        $this->sftpUploader = new SftpUploader();
    }

    public function handle(GenericEvent $event): void
    {
        
        $user = $event->getSubject();

        if ($user instanceof User && $event->hasArgument('em')) {
            /** @var ObjectManager $em */
            $em = $event->getArgument('em');
            /** @var UserRepository $repository */
            $repository = $em->getRepository(User::class);
            $alphabet = getenv('HASHIDS_ALPHABET');
            $encoder = new Hashids(User::class, 6, $alphabet);
            $dirSlug = $encoder->encode($user->getId());
            /** @var User $duplicate */
            $duplicate = $repository->findOneBy(['dirSlug' => $dirSlug]);

            if ($duplicate instanceof User) {
                $salt = 'App\Entity\DuplicateUser-'.date('YmdHis');
                $duplicateEncoder = new Hashids($salt, 7, $alphabet);
                $dirSlug = $duplicateEncoder->encode($user->getId());
            }

            $user->setDirSlug($dirSlug);

            $fs = new Filesystem();
            $images = ['photo', 'banner'];

            foreach ($images as $image) {
                $getter = sprintf('get%sProfile', ucfirst($image));
                $setter = sprintf('set%sProfile', ucfirst($image));
                if (!empty($user->{$getter}())) {
                    $parts = explode('/', $user->{$getter}());
                    $fileName = end($parts);
                    $newPath = 'uploads/users/'.$dirSlug.'/'.$fileName;
                    $publicPath = getenv('APP_PUBLIC_PATH');

                    $originFile = __DIR__.'/../../'.$publicPath.'/'.$user->{$getter}();
                    $targetFile = __DIR__.'/../../'.$publicPath.'/'.$newPath;

                    $localFilePath  = $user->{$getter}();
                    $remoteDir      = "uploads/users/".$dirSlug . '/';
                    $remoteFilePath = $fileName;

                    if ($fs->exists($originFile)) {
                        $this->sftpUploader->upload($localFilePath, $_ENV['SFTP_REMOTE_DIR'] . $remoteDir, $remoteFilePath);
                        // $fs->copy($originFile, $targetFile);
                        $fs->remove($originFile);

                        $user->{$setter}($newPath);
                    }
                }
            }

            if ($user->getRole() === 'ROLE_USER_GOVERNMENT' && !empty($user->getNpwpFile())) {
                // dd('masuk hndl12');
                $parts = explode('/', $user->getNpwpFile());
                $fileName = end($parts);
                $newPath = 'uploads/users/'.$dirSlug.'/'.$fileName;
                $publicPath = getenv('APP_PUBLIC_PATH');

                $originFile = __DIR__.'/../../'.$publicPath.'/'.$user->getNpwpFile();
                $targetFile = __DIR__.'/../../'.$publicPath.'/'.$newPath;

                if ($fs->exists($originFile)) {
                    $fs->copy($originFile, $targetFile);
                    $fs->remove($originFile);

                    $user->setNpwpFile($newPath);
                }
            }

            $em->persist($user);
            $em->flush();
        }
    }
}
