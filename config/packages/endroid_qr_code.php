<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @var ContainerBuilder $container */
$container->loadFromExtension('endroid_qr_code', [
    'writer' => 'png',
    'size' => 300,
    'margin' => 10,
    'error_correction_level' => 'low',
    'validate_result' => false,
]);
