<?php

use App\Controller\Media;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('media_gallery', '/gallery')
        ->controller([Media\MediaController::class, 'gallery'])
        ->methods(['GET'])
    ;

    $routes
        ->add('media_upload', '/upload')
        ->controller([Media\MediaController::class, 'upload'])
        ->methods(['POST'])
    ;

    $routes
        ->add('media_file_upload', '/file/upload')
        ->controller([Media\FileController::class, 'fileUpload'])
        ->methods(['POST'])
    ;

    $routes
        ->add('media_file_delete', '/file/delete')
        ->controller([Media\FileController::class, 'fileDelete'])
        ->methods(['POST'])
    ;
};
