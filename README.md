<h1 align="center">Lightbox de pagamentos Gerencianet</h1>

![Capa GitHub Lightbox Gerencianet](https://media-exp1.licdn.com/dms/image/C4D1BAQH9taNIaZyh_Q/company-background_10000/0/1603126623964?e=2159024400&v=beta&t=coQC_AK70vTYL3NdvbeIaeYts8nKumNHjvvIGCmq5XA)

## Sumário

- [Introdução](#introdução)
- [Como funciona o Lightbox](#como-funciona-o-lightbox)
- [Instalação da loja de demonstração](#instalação-da-loja-de-demonstração)
- [Configuração da loja de demonstração](#configuração-da-loja-de-demonstração)
- [Documentação Adicional](#documentação-adicional)
- [Licença](#licença)


## Introdução

A integração com o Lightbox da Gerencianet lhe permite exibir o formulário de coleta das informações de pagamento sobreposta à sua página de checkout. Por exemplo, quando o cliente adiciona os produtos no carrinho e fecha o pedido, apenas esmaecemos o fundo do seu site e o comprador visualiza uma nova janela modal para preenchimento dos dados de pagamento.

[Acesse nossa loja de demonstração](https://www.gerencianet.com.br/wp-content/themes/Gerencianet/lightbox/), e experimente como no seguinte exemplo:
![Gif lightbox Gerencianet](https://github.com/gerencianet/gn-lightbox-example/blob/master/frontend/assets/img/exemplo-lightbox.gif)

Neste exemplo utilizamos como dependência a SDK de PHP, que é responsável por realizar as requisições na a API Gerencianet. Mas o nosso Lightbox, lhe permite integrar sua aplicação também com as [SDKs Gerencianet em outras linguagens disponíveis](https://github.com/gerencianet).


## **Como funciona o Lightbox**
O Lightbox se comunica com a API da Gerencianet através de um back-end que deve ser desenvolvido utilizando uma de nossas SDKs disponíveis, de acordo com a necessidade e regra de negocio do sistema integrador. Sendo necessário o integrador desenvolver também a solução de retorno das notificações, utilizando da funcionalidade de callback para boleto e cartão, e o webhook para Pix.


## **Instalação da loja de demonstração**
Clone este repositório e execute o seguinte comando dentro do diretório `./backend`, para instalar as dependências.
```cmd
composer install
```


## **Configuração da loja de demonstração**

### **Definição das credenciais**
Para começar, você deve configurar os seguintes parâmetros no arquivo `./backend/index.php`. 
1. Instancie as informações `Client_Id`, `Client_Secret` da sua aplicação criada no painel da Gerencianet.
2. Informe no atributo `sandbox` igual a **true** se seu ambiente for *Homologação*, ou **false** se for *Produção*. 
3. Na variável `$expirationTime`, informe o tempo de expiração em dias (int). Necessário para gerar o boleto e Pix.
4. Se você utilizar cobranças Pix:  
  4.1. Informe no atributo `pix_cert` o diretório relativo com o nome do seu certificado no formato `.pem` referente ao ambiente escolhido (Homologação/Produção).  
  4.2. Na variável `$pixKey`, informe sua chave pix registrada na Gerencianet.

Caso ainda não tenha estes dados, siga os passos descritos em nossa documentação para [criar uma aplicação e obter as credenciais](https://dev.gerencianet.com.br/docs/api-pix-autenticacao-e-seguranca#section-obtendo-as-credenciais-da-aplica-o), [gerar o certificado do Pix](https://dev.gerencianet.com.br/docs/api-pix-autenticacao-e-seguranca#section-gerando-um-certificado-p12), e também [como registrar uma chave Pix](https://gerencianet.com.br/artigo/como-cadastrar-chaves-pix/).


### **Inserção do script Lightbox**
A maneira de iniciar o Lightbox, é inserindo em seu site um script fornecido pela Gerencianet que é responsável por carregar a janela de cobrança para o site.

Este script você [obtêm em nossa documentação](https://dev.gerencianet.com.br/docs/checkout-lightbox), informando o identificador de sua conta, e então será gerado um script semelhante ao seguinte:

```js
<script type='text/javascript'>var s=document.createElement('script');s.type='text/javascript';var v=parseInt(Math.random()*1000000);s.src='https://sandbox.gerencianet.com.br/v1/cdn/lightbox/identificador_da_conta/'+v;s.async=false;s.id='identificador_da_conta';if(!document.getElementById('identificador_da_conta')){document.getElementsByTagName('head')[0].appendChild(s);};$gn={validForm:true,processed:false,done:{},ready:function(fn){$gn.done=fn;}};</script>
```

:warning: Atente-se para utilizar o script gerado para o ambiente correto que deseja (Produção ou Homologação).

Tendo o script gerado, você deve inserí-lo na **linha 30** do arquivo `./frontend/index.html`.


## **Documentação Adicional**

Em nossa documentação técnica, você pode verificar mais [detalhes das funções do Lightbox Gerencinet](https://dev.gerencianet.com.br/docs/checkout-lightbox), e também outros detalhes de nossa API.

Se você ainda não tem uma conta digital da Gerencianet, [abra a sua agora](https://sistema.gerencianet.com.br/)!


## **Licença**
[MIT](LICENSE)
