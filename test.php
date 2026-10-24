<?php

use Doctrine\DBAL\DriverManager;
use OneToMany\PostgresBundle\Driver\LockManager;

require_once __DIR__.'/vendor/autoload.php';

$connectionParams = [
    'dbname' => 'fieldsheet',
    'user' => 'fieldsheet',
    'password' => '',
    'host' => 'localhost',
    'driver' => 'pdo_pgsql',
];

$connection = DriverManager::getConnection($connectionParams);

$lm = new LockManager($connection);
$lock = $lm->lock('index:1');

print_r($lock);
