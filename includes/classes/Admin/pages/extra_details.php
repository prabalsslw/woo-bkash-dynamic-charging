<?php

if ( isset( $trx ) && $trx ) {
	?>

	<p>Thank you for your payment using bKash online payment gateway. Here is your payment details</p>

	<table id="extra-detail-table" class="woocommerce-table order_details" aria-describedby="extra details">
		<tr>
			<td>Payment Method</td>
			<td>bKash Online payment Gateway - Dynamic Charge</td>
		</tr>
		<tr>
			<td>Transaction ID</td>
			<td><?php
				esc_html_e( $trx->getTrxID() ?? '', "woo-bkash-dynamic-charging" ); ?></td>
		</tr>
		<tr>
			<td>Payment Status</td>
			<td><?php
				esc_html_e( $trx->getStatus() ?? '', "woo-bkash-dynamic-charging" ); ?></td>
		</tr>
	</table>

	<?php
}
?>
