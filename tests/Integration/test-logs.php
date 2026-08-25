<?php
require __DIR__ . '/../../vendor/autoload.php';

use Nimbbl\Api\Logger;

$logger = Logger::getInstance();
Logger::enableDebugLogging(); // emit DEBUG as well

$logger->debug('debug test');
$logger->info('info test');
$logger->warning('warning test');
$logger->error('error test');
$logger->critical('critical test');
$logger->exception('exception test', new \Exception('boom'));

echo "Logs written to default log file and stdout (if CLI).\n";
