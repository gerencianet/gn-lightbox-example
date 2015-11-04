<?php

  $postItems = isset($_POST['items']) ? $_POST['items'] : null;
  $postCustomer = isset($_POST['customer']) ? $_POST['customer'] : null;
  $postShippingAddress = isset($_POST['shippingAddress']) ? $_POST['shippingAddress'] : null;
  $postPayment = isset($_POST['payment']) ? $_POST['payment'] : null;

  require_once __DIR__ . '/vendor/autoload.php';

  use Gerencianet\Exception\GerencianetException;
  use Gerencianet\Gerencianet;

  $options = [
    "client_id" => "your_client_id",
    "client_secret" => "your_client_secret",
    "sandbox" => true,
    "debug" => false
  ];

  $items = array();
  foreach($postItems as $item) {
    $i = null;
    $i = [
      'name' => $item['name'],
      'amount' => (int)$item['amount']
    ];

    switch($item['code']) {
      case 1: $i['value'] = 12000; break;
      case 2: $i['value'] = 4000; break;
    }

    $items[] = $i;
  }

  $shippings = array();
  $shippings[] = [
    'name' => 'Frete',
    'value' => 3500
  ];

  $chargeBody = [
    'items' => $items,
    'shippings' => $shippings,
    'metadata' => [
      'custom_id' => 'ID_001',
      'notification_url' => 'http://localhost/notification'
    ]
  ];

  $customer = [
    'name' => $postCustomer['name'],
    'email' => $postCustomer['email'],
    'cpf' => $postCustomer['cpf'],
    'birth' => $postCustomer['birth'],
    'phone_number' => $postCustomer['phone']
  ];

  if($postCustomer['person'] === 'juridical') {
    $customer['juridical_person'] = [
      'corporate_name' => $postCustomer['corporate_name'],
      'cnpj' => $postCustomer['cnpj']
    ];
  }

  if($postShippingAddress) {
    $shippingAddress = [
      'street' => $postShippingAddress['street'],
      'number' => $postShippingAddress['number'],
      'neighborhood' => $postShippingAddress['neighborhood'],
      'city' => $postShippingAddress['city'],
      'state' => $postShippingAddress['state'],
      'zipcode' => $postShippingAddress['zipcode']
    ];

    if(isset($postShippingAddress['complement']))
      $shippingAddress['complement'] = $postShippingAddress['complement'];

    $customer['address'] = $shippingAddress;
  }

  try {
    // Starts the main class from API
    $apiGN = new Gerencianet($options);

    // Creates the charge
    $charge = $apiGN->createCharge([], $chargeBody);

    // Checks the received response
    if($charge['code'] == '200') {

      $params = [
        'id' => $charge['data']['charge_id']
      ];

      $paymentBody = [
        'payment' => []
      ];

      if($postPayment['method'] == 'credit_card'){
        $billingAddress = [
          'street' => $postPayment['address']['street'],
          'number' => $postPayment['address']['number'],
          'neighborhood' => $postPayment['address']['neighborhood'],
          'city' => $postPayment['address']['city'],
          'state' => $postPayment['address']['state'],
          'zipcode' => $postPayment['address']['zipcode']
        ];

        if(isset($postPayment['address']['complement']))
          $shippingAddress['complement'] = $postPayment['address']['complement'];


        $paymentBody['payment']['credit_card'] = [
          'installments' => (int)$postPayment['installments'],
          'billing_address' => $billingAddress,
          'payment_token' => $postPayment['payment_token'],
          'customer' => $customer
        ];
      } else {
        $paymentBody['payment']['banking_billet'] = [
          'expire_at' => '2020-12-31',
          'customer' => $customer
        ];
      }

      $payment = $apiGN->payCharge($params, $paymentBody);

      echo json_encode($payment);
    } else {
      echo json_encode($charge);
    }

  } catch(GerencianetException $e) {
    $err = [
      'code' => $s->code,
      'error' => $e->error,
      'error_description' => $e->errorDescription
    ];
    echo json_encode($err);
  } catch(Exception $ex) {
    $err = [
      'error' => $ex->getMessage()
    ];
    echo json_encode($err);
  }

?>
