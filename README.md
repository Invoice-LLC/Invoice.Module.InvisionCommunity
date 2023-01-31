<h1>Invoice Payment Gateway</h1>

<h3>Установка</h3>

1. Скачайте [плагин](https://github.com/Invoice-LLC/Invoice.Module.InvisionCommunity/archive/master.zip), затем распакуйте его в корневую директорию сайта
2. Перейдите в папку **%папка сайта%\applications\nexus\sources\Gateway** и отредактируйте в файле Gateway.php функцию **gateways()**
следующим образом:
```php
public static function gateways()
{
    $return = array(
        'Stripe'		=> 'IPS\nexus\Gateway\Stripe',
        'Braintree'		=> 'IPS\nexus\Gateway\Braintree',
        'PayPal'		=> 'IPS\nexus\Gateway\PayPal',
        'AuthorizeNet'	=> 'IPS\nexus\Gateway\AuthorizeNet',
        'TwoCheckout'	=> 'IPS\nexus\Gateway\TwoCheckout',
        'Manual'		=> 'IPS\nexus\Gateway\Manual',
        'Invoice'       => 'IPS\nexus\Gateway\Invoice' // Добавьте вот эту строчку
    );
    
    if ( \IPS\NEXUS_TEST_GATEWAYS )
    {
        $return['Test'] = 'IPS\nexus\Gateway\Test';
    }
    
    return $return;
}
```
3. В админ-панели перейдите во вкладку **Commerce->Settings->Payment methods**, затем нажмите "Create New"
4. В открывшейся форме выберите **gateway__invoice**, затем нажмите "Save"
5. Заполните форму следующим образом:
![Imgur](https://imgur.com/eyR3P33.png)

<br> (Все данные можно получить в [личном кабинете Invoice](https://lk.invoice.su/))
<br>Api ключ и Merchant Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>
6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
      с типом **WebHook** и адресом: **%URL сайта%/applications/nexus/interface/gateways/invoice.php**<br>
      ![Imgur](https://imgur.com/lMmKhj1.png)
