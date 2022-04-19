<?php

PaymentPage\Migration::instance()->fix_table_structure(true);

echo '<p>' . __( "Database Migration.", "payment-page" ) . '</p>';