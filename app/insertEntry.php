<?php

use Dotenv\Dotenv;
use UniFi_API\Client;
use SleekDB\Store;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once 'vendor/autoload.php';

// Set-up logs
$log = new Logger(basename(__FILE__, '.php'));
if (filter_var($_ENV['DEBUG_MODE'], FILTER_VALIDATE_BOOLEAN)){
    $log->pushHandler(new StreamHandler('php://stdout', Level::Info));
} else {
    $log->pushHandler(new StreamHandler('php://stdout', Level::Error));
}

# Load configuration
$dotenv = Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();

# Set timezone
date_default_timezone_set($_ENV['DEFAULT_TIMEZONE']);
$actual_time = new DateTimeImmutable();
$time_window = $actual_time->modify($_ENV['TIME_WINDOW']);

# Init database
$store = new Store($_ENV['DATABASE_NAME'],$_ENV['DATABASE_PATH'],[  "auto_cache" => false,"timeout"=>false]);

// Instantiate controller connection 
$unifi_connection = new Client($_ENV['UNIFI_USER'], $_ENV['UNIFI_PASS'], $_ENV['UNIFI_URL'], $_ENV['UNIFI_SITE_ID'], $_ENV['UNIFI_VERSION']);
$unifi_connection->set_debug(filter_var($_ENV['DEBUG_UNIFI'], FILTER_VALIDATE_BOOLEAN));
$unifi_connection->login();

$clients = explode(',', $_ENV['CLIENTS']);
$clients_list = [];

# Basic script
foreach ($clients as $mac) {

    $entry = $unifi_connection->list_clients($mac)[0]->mac ?? "!$mac";
    array_push($clients_list, $entry);

}

# Add to database
$data = ["clients" => $clients_list, "createdAt" => $actual_time->getTimestamp()];
$results = $store->insert($data);

if ($results) {
    $log->info('Entry added', $data);
} else {
    $log->error('Something went wrong');
}