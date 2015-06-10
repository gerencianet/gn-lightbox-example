<?php

  $postItems = isset($_POST['items']) ? $_POST['items'] : null;
  $postCustomer = isset($_POST['customer']) ? $_POST['customer'] : null;
  $postShippingAddress = isset($_POST['shippingAddress']) ? $_POST['shippingAddress'] : null;
  $postPayment = isset($_POST['payment']) ? $_POST['payment'] : null;

  require_once __DIR__ . '/vendor/autoload.php';

  use Gerencianet\Gerencianet;
  use Gerencianet\Models\Address;
  use Gerencianet\Models\Customer;
  use Gerencianet\Models\GerencianetException;
  use Gerencianet\Models\Item;
  use Gerencianet\Models\Metadata;
  use Gerencianet\Models\Shipping;

  try {
    $apiKeyId = 'your_client_id';
    $apiKeySecret = 'your_client_secret';

    // Starts the main class from API, defining that will use the development environment
    $apiGN = new Gerencianet($apiKeyId, $apiKeySecret, true);

    // Creates the items array to API
    $items = array();
    foreach($postItems as $item) {
      $i = null;
      $i = new  Item();
      $i->name($item['name'])
        ->amount($item['amount']);

      switch($item['code']) {
        case 1: $i->value(12000); break;
        case 2: $i->value(4000); break;
      }

      $items[] = $i;
    }

    // Creates the customer to API
    $customer = new Customer();
    $customer->name($postCustomer['name'])
             ->email($postCustomer['email'])
             ->document($postCustomer['document'])
             ->birth($postCustomer['birth'])
             ->phoneNumber($postCustomer['phone']);

    if($postShippingAddress) {
      $shippingAddress = new Address();
      $shippingAddress->street($postShippingAddress['street'])
                      ->number($postShippingAddress['number'])
                      ->neighborhood($postShippingAddress['neighborhood'])
                      ->city($postShippingAddress['city'])
                      ->state($postShippingAddress['state'])
                      ->zipcode($postShippingAddress['zipcode']);

      if(isset($postShippingAddress['complement']))
        $shippingAddress->complement($postShippingAddress['complement']);

      $customer->address($shippingAddress);
    }

    // Creates the shipping to API
    $shipping = new Shipping();
    $shipping->name('Frete')
             ->value(3500);

    // Creates the metadata to API
    $metadata = new Metadata();
    $metadata->customId('ID_001')
             ->notificationUrl('http://localhost/notification');

    // Creates the charge
    $charge = $apiGN->createCharge()
                    ->addItems($items)
                    ->customer($customer)
                    ->addShipping($shipping)
                    ->metadata($metadata)
                    ->run();
    $response = $charge->response();

    // Checks the received response
    if($response['code'] == '200') {
      $chargeId = $response['charge']['id'];

      $payment = $apiGN->createPayment()
                       ->chargeId($chargeId)
                       ->method($postPayment['method']);

      if($postPayment['method'] == 'credit_card'){
        $billingAddress = new Address();
        $billingAddress->street($postPayment['address']['street'])
                       ->number($postPayment['address']['number'])
                       ->neighborhood($postPayment['address']['neighborhood'])
                       ->city($postPayment['address']['city'])
                       ->state($postPayment['address']['state'])
                       ->zipcode($postPayment['address']['zipcode']);

        if(isset($postPayment['address']['complement']))
          $shippingAddress->complement($postPayment['address']['complement']);

        $payment->billingAddress($billingAddress)
                ->installments($postPayment['installments'])
                ->paymentToken($postPayment['payment_token']);
      } else {
        $payment->expireAt('2020-12-31');
      }

      $payment->run();

      $response = $payment->response();

      echo Gerencianet::json($response);
    } else {
      echo Gerencianet::json('Error in createCharge');
    }

  } catch(GerencianetException $e) {
    Gerencianet::error($e);
  } catch(Exception $ex) {
    Gerencianet::error($ex);
  }

?>