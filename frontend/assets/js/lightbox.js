$gn.ready(function (variable) {
    /**
     * Defina os métodos de pagamento que deseja oferecer em seu lightbox
     * OPÇÕES:
     * - "pix"
     * - "banking_billet"
     * - "credit_card"
     */
    var payment_forms = ["pix", "banking_billet", "credit_card"];

    variable.lightbox(payment_forms);

    /**
     * Ao clicar no botão "Pagar", no carrinho, executa a seguinte função
     * enviando os dados dos produtos e frete para o backend
     */
    variable.jq('#button_lightbox').click(function (evt) {
        var data = {
            items: [],
            shippingCosts: SHIPPING * 100,
            //customer: false,
            //shippingAddress: false,
            actionForm: '../backend/index.php' // Local onde será enviada os dados para criação da cobrança
        };

        data.items = cart.map((item) => {
            return {
                code: item.code,
                name: item.name,
                value: item.price * 100,
                amount: item.amount
            }
        })

        /**
         * Função que realiza a chamada do lightbox da Gerencianet e exibe na tela
         */
        variable.show(data);
    });
});