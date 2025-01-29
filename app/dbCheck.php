<?php

use Dotenv\Dotenv;
use SleekDB\Store;
use SleekDB\Query;
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

# Fetch proper entries
$dbClients = $store->findBy([ "createdAt", "BETWEEN", [ $time_window->getTimestamp(), $actual_time->getTimestamp() ] ] );

function checkClientAvailability($client)
{
    return str_starts_with($client, "!");
}

$completed = [];

foreach($dbClients as $entry){
    
    $realAvailable = count(array_filter($entry['clients'], "checkClientAvailability")) ;
    $fullAvailable = count($entry['clients']);

    $msg = $realAvailable . " / " . $fullAvailable;
    $log->info($msg);

    if ($realAvailable === $fullAvailable){
        array_push($completed,true);
    } else {
        array_push($completed,false);
    }
}

if((count($completed) >= intval($_ENV['CHECK_COUNTER'])) && (count(array_filter($completed)) == count($completed))){
    $log->info('Turn off the lights');
    file_get_contents($_ENV['AUTOMATION_PLATFORM']);

    $trash = $store->deleteBy([ "createdAt", "BETWEEN", [ $time_window->getTimestamp(), $actual_time->getTimestamp() ] ], Query::DELETE_RETURN_RESULTS);
    $log->info("DELETED: ",$trash);
}
