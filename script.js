$gn.ready( function( variable ) {

  var payment_forms = [ "credit_card", "banking_billet" ];
  variable.lightbox( payment_forms );

  variable.jq( '#button_lightbox' ).click( function( evt ) {

    var data = {
      items: [ {
        code: 1,
        name: 'Item 1',
        value: 12000
      }, {
        code: 2,
        name: 'Item 2',
        value: 4000,
        amount: 2
      } ],
      shippingCosts: 3500,
      actionForm: 'http:///localhost/ex_lightbox/backend.php'
    };

    variable.show( data );

  } );

} );