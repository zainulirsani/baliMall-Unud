<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Command;
use App\Controller;
use App\Email;
use App\EventListener;
use App\EventSubscriber;
use App\Service;
use App\Twig;
use App\Validator\Constraints;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/** @var Loader $loader */
$loader->import('parameters.php');
$loader->import('admin_menu.php');
//$loader->import('app.php');

// https://symfony.com/doc/current/profiler.html#enabling-the-profiler-conditionally
//if (getenv('APP_ENV') === 'dev') {
//    /** @var ContainerInterface $container */
//    $container->setAlias(Profiler::class, 'profiler');
//}

return function (ContainerConfigurator  $configurator) {
    $services = $configurator
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services
        ->load('App\\', '../src/*')
        ->exclude('../src/{DependencyInjection,Entity,Helper,Migrations,Tests,Kernel.php}')
    ;

    $services
        ->load('App\\Controller\\', '../src/Controller')
        ->tag('controller.service_arguments')
    ;

    $services
        ->load('App\\Plugins\\', '../src/Plugins')
        ->tag('controller.service_arguments')
    ;

    $services
        ->set(Command\CancelOrderCommand::class, Command\CancelOrderCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\SendReportDJPCommand::class, Command\SendReportDJPCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\CheckExpiredBniCommand::class, Command\CheckExpiredBniCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\GenerateUserPpkTreasurerCommand::class, Command\GenerateUserPpkTreasurerCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;
    
    $services
        ->set(Command\GeneratePpkTreasurerOrderCommand::class, Command\GeneratePpkTreasurerOrderCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\GenerateVaBniSatkerCommand::class, Command\GenerateVaBniSatkerCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\InquiryVaBniCommand::class, Command\InquiryVaBniCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\CheckStatusBpdCcCommand::class, Command\CheckStatusBpdCcCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\UpdateVaBniCommand::class, Command\UpdateVaBniCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\InsertKldiCommand::class, Command\InsertKldiCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\GetDokuPaidCommand::class, Command\GetDokuPaidCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\ChangeStatusOrderBugPaidCommand::class, Command\ChangeStatusOrderBugPaidCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\GenerateTaxTypeUsingPaymentMethodCommand::class, Command\GenerateTaxTypeUsingPaymentMethodCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\CheckQRISStatusCommand::class, Command\CheckQRISStatusCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\CheckVAStatusCommand::class, Command\CheckVAStatusCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\CancelDokuFailedOrderCommand::class, Command\CancelDokuFailedOrderCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\InsertProductCategoriesToStoreCommand::class, Command\InsertProductCategoriesToStoreCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\CheckDokuStatusCommand::class, Command\CheckDokuStatusCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\CheckMidtransStatusCommand::class, Command\CheckMidtransStatusCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\GenerateShopIdErzapCommand::class, Command\GenerateShopIdErzapCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\GenerateProductSKUCommand::class, Command\GenerateProductSKUCommand::class)
        ->args([service('doctrine'), service('monolog.logger')]);

    $services
        ->set(Command\GenerateMonthlyMerchantReportCommand::class, Command\GenerateMonthlyMerchantReportCommand::class)
        ->args([service('doctrine'), service('app.service.twig')])
    ;

    $services
        ->set(Command\PopulateDummyUserCommand::class, Command\PopulateDummyUserCommand::class)
        ->args([service('doctrine')])
    ;

    $services
        ->set(Command\ProcessMailQueueCommand::class, Command\ProcessMailQueueCommand::class)
        ->args([service('doctrine'), service('swiftmailer.mailer.default')])
    ;

    $services
        ->set(Command\GenerateUserOperatorOwnerCommand::class, Command\GenerateUserOperatorOwnerCommand::class)
        ->args([service('doctrine'), service('monolog.logger')])
    ;

    $services
        ->set(Command\TestMailCommand::class, Command\TestMailCommand::class)
        ->args([service('app.email.base_mail')])
    ;

    $services
        ->set(Email\BaseMail::class, Email\BaseMail::class)
        ->args([service('twig'), service('app.service.swift_mailer'), '%mail_sender%'])
        ->alias('app.email.base_mail', Email\BaseMail::class)
    ;

    $services
        ->set(EventListener\UserLoginListener::class, EventListener\UserLoginListener::class)
        ->args([service('doctrine.orm.default_entity_manager'), service('monolog.logger'), '%cart_config%'])
    ;

    $services
        ->set(EventListener\UserLogoutListener::class, EventListener\UserLogoutListener::class)
        ->args([service('doctrine.orm.default_entity_manager'), '%cart_config%'])
    ;

    $services
        ->set(EventSubscriber\MaintenanceSubscriber::class, EventSubscriber\MaintenanceSubscriber::class)
        ->args([service('twig'), '%maintenance_mode%'])
    ;

    $services
        ->set(EventSubscriber\SettingSubscriber::class, EventSubscriber\SettingSubscriber::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set(EventSubscriber\UserSubscriber::class, EventSubscriber\UserSubscriber::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set(EventSubscriber\RajaOngkirSubscriber::class, EventSubscriber\RajaOngkirSubscriber::class)
        ->args(['%raja_ongkir_config%', service('monolog.logger')])
    ;

    $services
        ->set(EventSubscriber\ControllerSubscriber::class, EventSubscriber\ControllerSubscriber::class)
        ->args([service('service_container'), '%ajax_csrf_token_id%'])
    ;

    $services
        ->set(EventSubscriber\LocaleSubscriber::class, EventSubscriber\LocaleSubscriber::class)
        ->args(['%kernel.default_locale%', '%admin_url%', '%supported_locales%'])
    ;

    $services
        ->set(EventSubscriber\ProductSubscriber::class, EventSubscriber\ProductSubscriber::class)
        ->args([service('doctrine.orm.default_entity_manager'), service('monolog.logger')])
    ;

    $services
        ->set(Controller\CustomExceptionController::class, Controller\CustomExceptionController::class)
        ->args([service('service_container'), service('monolog.logger'), service('twig')])
        ->public()
    ;

    $services
        ->set(Service\AppTwigService::class, Service\AppTwigService::class)
        ->args([service('kernel')])
        ->alias('app.service.twig', Service\AppTwigService::class)
        ->public()
    ;

    $services
        ->set(Service\CartService::class, Service\CartService::class)
        ->args(['%cart_config%'])
        ->public()
    ;

    $services
        ->set(Service\SendInBlueMailService::class, Service\SendInBlueMailService::class)
        ->args(['%send_in_blue_key%', service('monolog.logger')])
        ->public()
    ;

    $services
        ->set(Service\SwiftMailerService::class, Service\SwiftMailerService::class)
        ->args([service('swiftmailer.mailer.default'), service('monolog.logger')])
        ->alias('app.service.swift_mailer', Service\SwiftMailerService::class)
        ->public()
    ;

    $services
        ->set(Service\RajaOngkirService::class, Service\RajaOngkirService::class)
        ->args(['%raja_ongkir_config%', service('monolog.logger')])
        ->public()
    ;

    $services
        ->set(Service\SamitraService::class, Service\SamitraService::class)
        ->args([service('monolog.logger')])
        ->public()
    ;

    $services
        ->set(Service\FileUploader::class, Service\FileUploader::class)
        ->args(['%upload_dir_path%'])
        ->public()
    ;

    $services
        ->set(Service\QrCodeGenerator::class, Service\QrCodeGenerator::class)
        ->args(['%upload_dir%', '%upload_dir_path%'])
        ->public()
    ;

    $services
        ->set('validator.duplicate_slug', Constraints\DuplicateSlugValidator::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set('validator.product_price_check', Constraints\ProductPriceCheckValidator::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set('validator.reserved_names', Constraints\ReservedNamesValidator::class)
        ->args([service('doctrine.orm.default_entity_manager'), '%store_reserved_names%'])
    ;

    $services
        ->set('validator.strong_user_password', Constraints\StrongUserPasswordValidator::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set('validator.valid_dob', Constraints\ValidDobDateValidator::class)
        ->args([service('doctrine.orm.default_entity_manager')])
    ;

    $services
        ->set(Twig\AppExtension::class, Twig\AppExtension::class)
        ->args([service('service_container')])
    ;

    $services
        ->set(Twig\AppFunction::class, Twig\AppFunction::class)
        ->args([service('service_container')])
    ;

    $services
        ->set(Twig\OrderExtension::class, Twig\OrderExtension::class)
        ->args([service('service_container')])
    ;

    $services
        ->set(Twig\ProductExtension::class, Twig\ProductExtension::class)
        ->args([service('service_container')])
    ;
};
