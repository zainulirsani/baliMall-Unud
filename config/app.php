<?php

/**
 * Ideally this would solve the problem with getting parameter values from database.
 * But it's not working at the moment because of the container cache.
 * Meaning any config file *must* be changed to clear the cache and trigger this file.
 * So let's just skip this file now -- until further research.
 */

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

try {
    $config = new Configuration();
    $parameter = ['url' => getenv('DATABASE_URL')];
    $connection = DriverManager::getConnection($parameter, $config);

    $slugs = ['admin_url', 'page_title'];
    $sql = 'SELECT id, slug, name, type, default_value, options FROM setting WHERE slug IN (?)';
    $statement = $connection->executeQuery($sql, [$slugs], [Connection::PARAM_STR_ARRAY]);

    if ($results = $statement->fetchAll()) {
        // Source: https://stackoverflow.com/questions/6661530/php-multidimensional-array-search-by-value
        //$key = array_search($slugs[0], array_column($results, 'slug'), false); // To get single result
        //$keys = array_keys(array_column($results, 'slug'), $slugs[0]); // To get multiple results

        /** @var ContainerBuilder $container */
        $container->setParameter('admin_url', 'backend');
    }
} catch (DBALException | Exception $e) {
    // Oops! There's an exception occur with the connection to the DB
    // Do nothing as the default value has been set before
}
