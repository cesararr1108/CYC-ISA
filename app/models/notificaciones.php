<?php
require_once '../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

$factory = (new Factory)
    ->withServiceAccount('../../firebase/service-account.json');

$messaging = $factory->createMessaging();

$notification = Notification::create(
    'Nuevo pedido',
    'Se ha creado un nuevo pedido'
);

$message = CloudMessage::withTarget('token', $token)
    ->withNotification($notification);

$messaging->send($message);