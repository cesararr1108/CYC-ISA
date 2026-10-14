<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount(__DIR__ . '/cyc-notifications-push-firebase-adminsdk-fbsvc-cb13a5f684.json');

$messaging = $factory->createMessaging();