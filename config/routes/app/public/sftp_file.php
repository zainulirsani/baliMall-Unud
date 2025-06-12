<?php

use App\Controller\Sftp\SftpController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes
        ->add('show_file', '/file/{path}')
        ->controller([SftpController::class, 'showFile'])
        ->methods(['GET'])
        ->requirements(['path' => '.+'])
    ;

    $routes
        ->add('download_file', '/file?path={path}')
        ->controller([SftpController::class, 'downloadFile'])
        ->methods(['GET'])
        ->requirements(['path' => '.+', 'url' => '(http[s]?:\/\/)?([^\/\s]+\/)(.*)'])
    ;
};
