<?php

\define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
\IPS\Session\Front::i();

$postData = file_get_contents('php://input');
$notification = json_decode($postData, true);

/* Load Transaction */
try
{
    $transaction = \IPS\nexus\Transaction::load( $notification['order']['id'] );

    if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
    {
        die('Transaction not found');
    }
}
catch ( \OutOfRangeException $e )
{
   die ("Not Found");
}

$gateway = \IPS\nexus\Gateway::load($transaction->method->id);

if ( !( $gateway instanceof \IPS\nexus\Gateway\Invoice ) )
{
    die('Gateway not found');
}
$settings = json_decode( $gateway->settings, TRUE );

$invoice = \IPS\nexus\Invoice::load( $notification['order']['id'] );
$id = $notification["order"]["id"];
$type = $notification["notification_type"];
$signature = $notification["signature"];
$key = $settings['api_key'];

if($signature != md5($notification['id'].$notification["status"].$key) ){
    die( "Wrong signature" );
}

if($type == "pay") {

    if($notification["status"] == "successful") {
        $invoice->markPaid();
        $transaction->status = \IPS\nexus\Transaction::STATUS_PAID;
        $transaction->save();
        $transaction->sendNotification();
        die( "payment successful" );
    }
    if($notification["status"] == "error") {
        die( "payment failed" );
    }
}

return "null";
