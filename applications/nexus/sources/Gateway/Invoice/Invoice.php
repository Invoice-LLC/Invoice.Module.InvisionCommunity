<?php

namespace IPS\nexus\Gateway;

require "InvoiceSDK/GET_TERMINAL.php";
require "InvoiceSDK/RestClient.php";
require "InvoiceSDK/common/SETTINGS.php";
require "InvoiceSDK/common/ORDER.php";
require "InvoiceSDK/CREATE_TERMINAL.php";
require "InvoiceSDK/CREATE_PAYMENT.php";




class _Invoice extends \IPS\nexus\Gateway
{
    const SUPPORTS_REFUNDS = false;
    const SUPPORTS_PARTIAL_REFUNDS = false;


    public function checkValidity(\IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress = NULL, \IPS\nexus\Customer $customer = NULL, $recurrings = array())
    {
        return parent::checkValidity($amount, $billingAddress, $customer, $recurrings);
    }

    public function auth(\IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL, $recurrings = array(), $source = NULL)
    {
        $settings = json_decode($this->settings, TRUE);
        $amount = $transaction->amount->amount;


        $request = new CREATE_PAYMENT();
        $request->order = $this->getOrder($amount, $transaction->id);
        $request->settings = $this->getSettings($this->getTerminal($settings));
        $request->receipt = $this->getReceipt();


        $response = (new RestClient($settings['login'], $settings['api_key']))->CreatePayment($request);

        if ($response == null or isset($response->error)) throw new Exception('Payment error');

        $payment_url = $response->payment_url;

        $extra = $transaction->extra;

        $extra['id'] = $response->id;

        $transaction->extra = $extra;

        $transaction->save();

        \IPS\Output::i()->redirect(\IPS\Http\Url::external($payment_url));
    }

    /**
     * @return INVOICE_ORDER
     */
    public function getOrder($sum, $id)
    {
        $order = new INVOICE_ORDER();
        $order->amount = $sum;
        $order->id = $id . "-" . bin2hex(random_bytes(5));
        $order->currency = "RUB";

        return $order;
    }

    /**
     * @return SETTINGS
     */
    private function getSettings($terminal)
    {
        $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

        $settings = new SETTINGS();
        $settings->terminal_id = $terminal;
        $settings->success_url = $url;
        $settings->fail_url = $url;

        return $settings;
    }

    /**
     * @return ITEM
     */
    private function getReceipt()
    {
        $receipt = array();
        return $receipt;
    }

    /**
     * Settings
     *
     * @param	\IPS\Helpers\Form	$form	The form
     * @return	void
     */
    public function settings(&$form)
    {
        $settings = json_decode($this->settings, TRUE);

        $form->add(new \IPS\Helpers\Form\Text('invoice_login', $settings['login'], TRUE));
        $form->add(new \IPS\Helpers\Form\Text('invoice_api_key', $settings['api_key'], TRUE));
        $form->add(new \IPS\Helpers\Form\Text('invoice_terminal_name', $settings['terminal_name'], TRUE));
    }

    public function getTerminal($settings)
    {
        if (!file_exists("invoice_tid")) file_put_contents("invoice_tid", '');
        $tid = file_get_contents('invoice_tid');

        $terminal = new GET_TERMINAL();
        $terminal->alias =  $tid;
        $info = (new RestClient($settings['login'], $settings['api_key']))->GetTerminal($terminal);

        if ($tid == null or empty($tid) || $info->id == null || $info->id != $terminal->alias) {
            $request = new CREATE_TERMINAL();
            $request->name = $settings['terminal_name'];
            $request->description = "InvisionCommunity Terminal";
            $request->defaultPrice = 0;
            $request->type = "dynamical";
            $response = (new RestClient($settings['login'], $settings['api_key']))->CreateTerminal($request);

            if ($response == null or isset($response->error)) throw new Exception('Terminal error');

            $tid = $response->id;
            file_put_contents('invoice_tid', $tid);
        }

        return $tid;
    }

    public function testSettings($settings)
    {
        return $settings;
    }
}
