const express = require('express');
const fs = require('fs');
const cors = require('cors');
const https = require('https');
const logger = require('morgan');
const Gerencianet = require('gn-api-sdk-node');

const options = {
  sandbox: true,
  client_id: 'Client_Id_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  client_secret: 'Client_Secret_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  pix_cert: './../certs/developmentCertificate.pem'
};

const pixKey = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx";

const produts = JSON.parse(fs.readFileSync('./../db/products.json')); // Carrega os produtos cadastrados no arquivo products.json no banco de dado

const httpsOptions = {
  cert: fs.readFileSync('/usr/app/certs/fullchain.pem'), // Certificado fullchain do dominio
  key: fs.readFileSync('/usr/app/certs/privkey.pem'), // Chave privada do domínio
  ca: fs.readFileSync('/usr/app/certs/chain-pix-prod.crt'), // Certificado público da Gerencianet
  minVersion: 'TLSv1.2',
  requestCert: false,
  rejectUnauthorized: false //Mantenha como false para que os demais endpoints da API não rejeitem requisições sem MTLS
};

const app = express();
const httpsServer = https.createServer(httpsOptions, app);
const PORT = 443;

app.use(logger('dev')); // Comente essa linha caso não queira que seja exibido o log do servidor no seu console
app.use(express.json());
app.use(cors());
app.use(express.urlencoded({ extended: false }));

app.post('/', async (req, res) => {
  const gerencianet = new Gerencianet(options); // Inicia a API da Gerencianet

  dadosRequisicao = JSON.parse(req.body['data']); // Recebe os dados da requisição

  const postItems = dadosRequisicao['items']; // Extrai os itens
  const postCliente = dadosRequisicao['customer']; // Extrai os dados do cliente
  const postEnderecoDeEntrega = dadosRequisicao['shippingAddress']; // Extrai o endereço de entrega
  const postPagamento = dadosRequisicao['payment']; // Extrai o método de pagamento
  const postValorDoFrete = dadosRequisicao['shippingCosts']; // Extrai o valor do frete

  const arrayItems = [];
  Object.keys(postItems).forEach((key) => {
    // Transforma os itens do carrinho em array com suas informações
    arrayItems.push({
      name: key,
      about: postItems[key]
    });
  });

  const itensComprandos = [];
  let valorTotal = 0;
  Object.keys(arrayItems).forEach((item) => {
    // Percorre os itens do carrinho, verifica seu valor na variavel products pela sua propriedade code e adiciona os itens comprados no array itensComprandos

    const itemComprado = arrayItems[item].about;
    const produto = produts.find((produto) => produto.code === itemComprado.code);

    itensComprandos.push({
      name: produto.name, // Nome do produto
      value: produto.price * 100, // Valor em centavos multiplicado por 100 para ser expresso em reais
      amount: parseInt(itemComprado.amount) // Quantidade do produto
    });

    valorTotal += produto.price * itemComprado.amount; // Soma o valor total dos produtos
  });

  let cobranca;
  let status;

  if (postPagamento.method === 'pix') {
    // Verifica se o método de pagamento é Pix

    const expiracao = {
      expiracao: expirationTime * 86400 // Expiração definida em segundos
    };

    const valorTotalReais = valorTotal + postValorDoFrete / 100; // Soma o valor total dos produtos com o valor do frete convertido em reais
    const original = {
      original: valorTotalReais.toFixed(2) // Valor total da compra
    };
    const chave = pixKey; // Chave Pix cadastrada na Gerencianet  // ed402ce5-8726-4b0a-96b2-51f4c3717eec Valmir

    let devedor;
    if (postCliente.person === 'natural') {
      // Verifica se o cliente é pessoa física ou jurídica
      devedor = {
        cpf: postCliente.cpf,
        nome: postCliente.name
      };
    } else if (postCliente.person === 'juridical') {
      devedor = {
        cnpj: postCliente.cnpj,
        nome: postCliente.name
      };
    }

    const body = {
      // Informações da cobrança
      calendario: expiracao,
      devedor: devedor,
      valor: original,
      chave: chave
    };

    let pixc;
    let qr;
    console.log(body);
    try {
      pixc = await gerencianet.pixCreateImmediateCharge([], body); // Cria a cobrança Pix

      console.log(pixc);
    } catch (error) {
      console.log(error);

      cobranca = error;
    }

    if (pixc && pixc.txid) {
      // Verifica a resposta recebida
      try {
        let params = {
          id: pixc.loc.id
        };

        qr = await gerencianet.pixGenerateQRCode(params); // Gera o QR Code

        console.log(qr);

        status = 200;

        cobranca = {
          pix: pixc,
          qrcode: qr,
          payment: {
            method: 'pix'
          }
        };
      } catch (error) {
        console.log(error);
      }
    } else {
      status = 400;
    }
  } else {
    const customer = {
      // Monta um objeto com as informações do cliente
      name: postCliente.name,
      email: postCliente.email,
      cpf: postCliente.cpf,
      birth: postCliente.birth,
      phone_number: postCliente.phone,
      address: postEnderecoDeEntrega
    };

    if (postCliente.person === 'juridical') {
      // Verifica se o cliente é pessoa jurídica e caso seja, adiciona as informações da empresa
      customer['juridical_person'] = {
        corporate_name: postCliente.corporate_name,
        cnpj: postCliente.cnpj
      };
    }

    const payment = {};
    if (postPagamento.method === 'banking_billet') {
      // Verifica se o método de pagamento é boleto e caso seja, adiciona as informações de pagamento do boleto
      payment['banking_billet'] = {
        // Monta um objeto com as informações do pagamento por boleto
        expire_at: dataVencimento.format('YYYY-MM-DD'),
        customer: customer
      };
    } else if (postPagamento.method === 'credit_card') {
      // Verifica se o método de pagamento é cartão de crédito e caso seja, adiciona as informações de pagamento do cartão
      payment['credit_card'] = {
        // Monta um objeto com as informações do pagamento por cartão de crédito
        customer: customer,
        installments: parseInt(postPagamento.installments),
        payment_token: postPagamento.payment_token,
        billing_address: postEnderecoDeEntrega
      };
    }
    const shippings = [
      {
        // Monta um objeto com as informações de custo de envio
        name: 'Default Shipping Cost',
        value: postValorDoFrete
      }
    ];

    const body = {
      // Monta um objeto com todas as informações da cobrança
      items: itensComprandos,
      payment: payment,
      shippings: shippings
    };

    console.log(body);
    try {
      cobranca = await gerencianet.oneStep([], body);

      status = 200;
    } catch (error) {
      cobranca = error;
      status = 400;
    }
  }

  try {
    console.log(cobranca);
    res.status(status).send({
      data: JSON.stringify(cobranca)
    });
  } catch (error) {
    console.error(error);
  }
});

httpsServer.listen(PORT, () => console.log(`Express server currently running on port ${PORT}`));