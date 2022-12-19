<?php

/**
 * Iniciação da SDK
 */
require_once __DIR__ . '/vendor/autoload.php';

use Gerencianet\Exception\GerencianetException;
use Gerencianet\Gerencianet;


/**
 * Definição das credenciais
 */
$options = [
    "client_id" => "Client_Id_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "client_secret" => "Client_Secret_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "pix_cert" => "./certs/developmentCertificate.pem",
    "sandbox" => true,
    "debug" => false,
    "timeout" => 30
];

$expirationTime = 5; // (int) quantidade de dias para vencimento do Boleto e Pix

$pixKey = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx";


/**
 * Recebendo dados do pedido
 */
$postItems = isset($_POST['items']) ? $_POST['items'] : null;
$postShipping = isset($_POST['shippingCosts']) ? $_POST['shippingCosts'] : null;
$postCustomer = isset($_POST['customer']) ? $_POST['customer'] : null;
$postShippingAddress = isset($_POST['shippingAddress']) ? $_POST['shippingAddress'] : null;
$postPayment = isset($_POST['payment']) ? $_POST['payment'] : null;

$json = file_get_contents("./db/products.json");
$products = json_decode($json);
$totalValue = 0;

foreach ($postItems as $item) {
    $i = null;
    $i = [
        'name' => $item['name'],
        'amount' => (int)$item['amount']
    ];

    // Observe que você deve obter os valores do produto da sessão/banco de dados.
    // O exemplo fornecido abaixo é apenas para fins ilustrativos
    foreach ($products as $product) {
        if ($product->code == $item['code']) {
            $i['value'] = $product->price * 100;
            $totalValue += $i['value'];
            break;
        }
    }
    $items[] = $i;
}


try {

    /**
     * Método de pagamento Pix
     */
    if ($postPayment['method'] == 'pix') {

        $body = [
            "calendario" => [
                "expiracao" => ((int)$expirationTime * 86400) // Expiração definida em segundos
            ],
            "valor" => [
                "original" => number_format(strval(($totalValue + (int)$postShipping) / 100), 2, '.', '')
            ],
            "chave" => $pixKey, // Chave pix da conta Gerencianet do recebedor
            "infoAdicionais" => [
                [
                    "nome" => "Produtos",
                    "valor" => "Valor total: " . number_format(($totalValue / 100), 2, ',', '.')
                ],
                [
                    "nome" => "Frete",
                    "valor" => "Valor: " . number_format(((int)$postShipping / 100), 2, ',', '.')
                ]
            ]
        ];

        if ($postCustomer['person'] === 'juridical') {
            $body['devedor'] = [
                'nome' => $postCustomer['corporate_name'],
                'cnpj' => $postCustomer['cnpj']
            ];
        } else {
            $body['devedor'] = [
                'nome' => $postCustomer['name'],
                'cpf' => $postCustomer['cpf']
            ];
        }

        $api = Gerencianet::getInstance($options);

        // Gera a cobrança Pix
        $pix = $api->pixCreateImmediateCharge([], $body);

        // Verifica a resposta recebida
        if ($pix['txid']) {

            $params = [
                'id' => $pix['loc']['id']
            ];

            // Obtém o QRCode da cobrança gerada
            $qrcode = $api->pixGenerateQRCode($params);

            $returnPix = [
                "code" => 200,
                "data" => [
                    "pix" => $pix,
                    "qrcode" => $qrcode
                ]
            ];

            echo json_encode($returnPix);
        } else {
            echo json_encode($pix);
        }
    } // #Método de pagamento Pix

    /**
     * Método de pagamento Boleto ou Cartão
     */
    else {
        $shippings[] = [
            'name' => 'Frete',
            'value' => (int)$postShipping
        ];

        $customer = [
            'name' => $postCustomer['name'],
            'email' => $postCustomer['email'],
            'cpf' => $postCustomer['cpf'],
            'birth' => $postCustomer['birth'],
            'phone_number' => $postCustomer['phone']
        ];

        if ($postCustomer['person'] === 'juridical') {
            $customer['juridical_person'] = [
                'corporate_name' => $postCustomer['corporate_name'],
                'cnpj' => $postCustomer['cnpj']
            ];
        }

        if ($postShippingAddress) {
            $shippingAddress = [
                'street' => $postShippingAddress['street'],
                'number' => $postShippingAddress['number'],
                'neighborhood' => $postShippingAddress['neighborhood'],
                'city' => $postShippingAddress['city'],
                'state' => $postShippingAddress['state'],
                'zipcode' => $postShippingAddress['zipcode']
            ];

            if (isset($postShippingAddress['complement']))
                $shippingAddress['complement'] = $postShippingAddress['complement'];

            $customer['address'] = $shippingAddress;
        }

        /**
         * Método de pagamento Cartão de Crédito
         */
        if ($postPayment['method'] == 'credit_card') {
            $billingAddress = [
                'street' => $postPayment['address']['street'],
                'number' => $postPayment['address']['number'],
                'neighborhood' => $postPayment['address']['neighborhood'],
                'city' => $postPayment['address']['city'],
                'state' => $postPayment['address']['state'],
                'zipcode' => $postPayment['address']['zipcode']
            ];

            if (isset($postPayment['address']['complement']))
                $shippingAddress['complement'] = $postPayment['address']['complement'];


            $payment['credit_card'] = [
                'installments' => (int)$postPayment['installments'],
                'billing_address' => $billingAddress,
                'payment_token' => $postPayment['payment_token'],
                'customer' => $customer
            ];
        }
        /**
         * Método de pagamento Boleto/Bolix
         */
        else {
            $expire = new DateTime();
            $expire = date_add($expire, date_interval_create_from_date_string("$expirationTime days"));

            $payment['banking_billet'] = [
                'expire_at' => $expire->format('Y-m-d'),
                'customer' => $customer
            ];
        }

        $chargeBody = [
            'items' => (array)$items,
            'shippings' => (array)$shippings,
            'payment' => (array)$payment
        ];

        unset($options['pix_cert']);
        $apiGN = new Gerencianet($options);

        $returnCharge = $apiGN->oneStep([], $chargeBody);

        echo json_encode($returnCharge);
    } // #Método de pagamento Boleto ou Cartão
} catch (GerencianetException $e) {
    $err = [
        'code' => $s->code,
        'error' => $e->error,
        'error_description' => $e->errorDescription
    ];
    echo json_encode($err);
} catch (Exception $ex) {
    $err = [
        'error' => $ex->getMessage()
    ];
    echo json_encode($err);
}
