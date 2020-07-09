<?php
namespace IPS\nexus\Gateway;

require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";



class _Invoice extends \IPS\nexus\Gateway {
    const SUPPORTS_REFUNDS = false;
    const SUPPORTS_PARTIAL_REFUNDS = false;


    public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress = NULL, \IPS\nexus\Customer $customer = NULL, $recurrings = array() )
    {
        return parent::checkValidity( $amount, $billingAddress, $customer, $recurrings );
    }

    public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, $recurrings = array(), $source = NULL ) {
        $settings = json_decode( $this->settings, TRUE );


        $amount = $transaction->amount->amount;

        $order = new INVOICE_ORDER($amount);
        $order->id = $transaction->id;

        $invoice_settings = new SETTINGS($this->getTerminal($settings));
        $invoice_settings->success_url = ( ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);

        $request = new CREATE_PAYMENT($order, $invoice_settings, []);
        $response = (new RestClient($settings['login'], $settings['api_key']))->CreatePayment($request);

        if($response == null or isset($response->error)) throw new Exception('Payment error');

        $payment_url = $response->payment_url;

        $extra = $transaction->extra;

        $extra['id'] = $response->id;

        $transaction->extra = $extra;

        $transaction->save();

        \IPS\Output::i()->redirect( \IPS\Http\Url::external( $payment_url ) );
    }

    /**
     * Settings
     *
     * @param	\IPS\Helpers\Form	$form	The form
     * @return	void
     */
    public function settings( &$form )
    {
        $settings = json_decode( $this->settings, TRUE );

        $form->add( new \IPS\Helpers\Form\Text( 'invoice_login', $settings['login'], TRUE ) );
        $form->add( new \IPS\Helpers\Form\Text( 'invoice_api_key', $settings['api_key'], TRUE ) );
        $form->add( new \IPS\Helpers\Form\Text( 'invoice_terminal_name', $settings['terminal_name'], TRUE ) );
    }

    public function getTerminal($settings) {
        if(!file_exists("invoice_tid")) file_put_contents("invoice_tid", '');
        $tid = file_get_contents('invoice_tid');
        if($tid == null or empty($tid)) {
            $request = new CREATE_TERMINAL($settings['terminal_name']);
            $response = (new RestClient($settings['login'], $settings['api_key']))->CreateTerminal($request);

            if($response == null or isset ($response->error)) throw new Exception('Terminal error');

            $tid = $response->id;
            file_put_contents('invoice_tid', $tid);
        }

        return $tid;
    }

    public function testSettings( $settings ) {
        return $settings;
    }

}

