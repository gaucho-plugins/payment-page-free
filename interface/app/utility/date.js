function payment_page_format_timestamp_for_current_user( timestamp, withTime = true ) {
  withTime = ( withTime === 'undefined' ? true : withTime );

  let date    = new Date(timestamp * 1000),
      options = {
        year  : 'numeric',
        month : 'long',
        day   : 'numeric'
      };

  if( withTime === false )
    return date.toLocaleDateString( payment_page_get_user_locale(), options );

  options.hour   = '2-digit';
  options.minute = '2-digit';

  return date.toLocaleTimeString( payment_page_get_user_locale(), options  );
}