<?php

namespace App;

use Exception;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public const CONFIG_EXTS = '.{php}';

    public function getCacheDir()
    {
        return $this->getProjectDir().'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return $this->getProjectDir().'/var/log';
    }

    public function registerBundles()
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';

        foreach ($contents as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->getEnvironment()])) {
                yield new $class();
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param LoaderInterface  $loader
     *
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getProjectDir().'/config';

        $container->setParameter('container.autowiring.strict_mode', true);
        $container->setParameter('container.dumper.inline_class_loader', true);

        $loader->load($confDir.'/packages/*'.self::CONFIG_EXTS, 'glob');

        if (is_dir($confDir.'/packages/'.$this->getEnvironment())) {
            $loader->load($confDir.'/packages/'.$this->getEnvironment().'/*'.self::CONFIG_EXTS, 'glob');
            //$loader->load($confDir.'/packages/'.$this->getEnvironment().'/**/*'.self::CONFIG_EXTS, 'glob');
        }

        $loader->load($confDir.'/services'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/services_'.$this->getEnvironment().self::CONFIG_EXTS, 'glob');
    }

    /**
     * @param RouteCollectionBuilder $routes
     *
     * @throws LoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        if (is_dir($confDir.'/routes/')) {
            $routes->import($confDir.'/routes/*'.self::CONFIG_EXTS, '/', 'glob');
        }

        if (is_dir($confDir.'/routes/'.$this->getEnvironment())) {
            $routes->import($confDir.'/routes/'.$this->getEnvironment().'/*'.self::CONFIG_EXTS, '/', 'glob');
            //$routes->import($confDir.'/routes/'.$this->getEnvironment().'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        }

        $routes->import($confDir.'/routes'.self::CONFIG_EXTS, '/', 'glob');
    }
}
