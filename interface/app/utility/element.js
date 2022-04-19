function payment_page_element_loader( type = '') {
  let response = '';

  response += '<div class="payment-page-application-loader-wrapper"' + ( type !== '' ? ' data-payment-page-loader-type="' + type + '"' : '' ) + '>';

  if( type === 'mini' ) {
    response += '<div><div></div></div>';
  } else if( PaymentPage.settings.loader_icon === '' ) {
    response += '<div>' +
                  '<div>' +
                    '<div>' +
                      '<div>' +
                        '<div>' +
                          '<div>' +
                          '</div>' +
                        '</div>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                '</div>';
  } else {
    response += '<img alt="loader" src="' + PaymentPage.settings.loader_icon + '"/>';
    response += '<div>' +
                  '<div>' +
                    '<div>' +
                      '<div>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                '</div>';
  }


  response += '</div>';

  return response;
}