<div class="short-code-payment-confirmation-outer">
  <div class="short-code-payment-confirmation-box">
    <?php if( isset( $title ) && !empty( $title ) ) : ?>
      <h3><?php echo esc_html( $title ); ?></h3>
    <?php endif; ?>

    <?php if( isset( $message ) && !empty( $message ) ) : ?>
      <h3><?php echo esc_html( $message ); ?></h3>
    <?php endif; ?>

    <div class="short-code-c-p-list-items">
      <?php if( isset( $item ) && !empty( $item ) ) : ?>
        <div><strong><?php echo __( "Item", "payment-page" ); ?>: </strong><?php echo esc_html( $item ); ?></div>
      <?php endif; ?>
      <?php if( isset( $customer_name ) && !empty( $customer_name ) ) : ?>
        <div><strong><?php echo __( "Customer Name", "payment-page" ); ?>: </strong><?php echo esc_html( $customer_name ); ?></div>
      <?php endif; ?>
      <?php if( isset( $customer_email ) && !empty( $customer_email ) ) : ?>
        <div><strong><?php echo __( "Email", "payment-page" ); ?>: </strong><?php echo esc_html( $customer_email ); ?></div>
      <?php endif; ?>
      <?php if( isset( $payment_date ) && !empty( $payment_date ) ) : ?>
        <div><strong><?php echo __( "Payment Date", "payment-page" ); ?>: </strong><?php echo esc_html( $payment_date ); ?></div>
      <?php endif; ?>
      <?php if( isset( $payment_amount ) && !empty( $payment_amount ) ) : ?>
        <div><strong><?php echo __( "Payment Amount", "payment-page" ); ?>: </strong><?php echo esc_html( $payment_amount ); ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .payment-confirmation-outer{
    font-family: inherit;
  }
  .c-p-list-items > div + div{
    margin-top: 10px;
  }
</style>