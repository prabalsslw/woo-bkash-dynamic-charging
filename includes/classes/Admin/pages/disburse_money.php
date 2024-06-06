<?php

use function bKash\PGW\DC\WooCommerceBkashDC;

?>
<style>
	.wocommerce-message.error {
		border-left-color: #e23e3e !important;
	}
</style>
<h1>
<?php
	esc_html_e( get_admin_page_title(), 'woo-bkash-dynamic-charging' );
?>
	</h1>
<br>
<form action="#" method="post">

	<table id="disburse-money-table" aria-describedby="disburse money">
		<tr>
			<td>
				<label for="amount" class="form-label">Amount *</label>
			</td>
			<td>
				<input name="amount" type="text" id="amount" placeholder="Amount" class="form-text-input"/>
			</td>
		</tr>
		<tr>
			<td>
				<label for="receiver" class="form-label">Receiver (bKash Personal Account Holder) *</label>
			</td>
			<td>
				<input name="receiver" type="tel" id="receiver" placeholder="Mobile number" class="form-text-input" value="<?php esc_attr_e( $receiver ?? '', 'woo-bkash-dynamic-charging' ); ?>" pattern="^(?:\+88|01)?\d{11}$"/>
			</td>
		</tr>
		<tr>
			<td>
				<label for="invoice" class="form-label">Invoice Number</label>
			</td>
			<td>
				<input name="invoice_no" type="text" id="invoice" placeholder="Invoice Number" class="form-text-input"/>
			</td>
		</tr>
	</table>

	<button class="button button-primary" type="submit">Transfer</button>
</form>
<br>

<?php

if ( isset( $trx ) && is_string( $trx ) && ! empty( $trx ) ) {
	// FAILED TO GET BALANCES
	?>
	<div id="message" class="bKash-hero-div woocommerce-message bKash-error">
		<p>
		<?php
			esc_html_e( $trx, 'woo-bkash-dynamic-charging' );
		?>
			</p>
	</div>
	<?php
} elseif ( isset( $trx['trxID'] ) && is_array( $trx ) ) {
	// GOT TRANSACTION
	?>
	<div class="gateway-banner bKash-hero-div bKash-success">
		<img style="max-width: 90px; margin: 10px 5px" alt="bKash logo" src="<?php echo esc_url( WooCommerceBkashDC()->pluginUrl() . '/assets/images/logo.png' ); ?>"/>
		<p class="main">
			<strong>Transaction ID: 
			<?php
				esc_html_e( $trx['trxID'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
				</strong>
		</p>
		<hr>
		<p>Disbursed To (bKash Customer Account):
			<b>
			<?php
			esc_html_e( $trx['receiverMSISDN'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
			</p>
		<p>Amount: <b>
		<?php
				esc_html_e( $trx['amount'] ?? '', 'woo-bkash-dynamic-charging' )." ".esc_html_e( $trx['currency'] ?? '', 'woo-bkash-dynamic-charging' );
		?>
				</b></p>
		<hr>
		<ul>
			<li>Invoice Number:
				<strong>
				<?php
					esc_html_e( $trx['merchantInvoiceNumber'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
			<li>
				Completed At:
				<strong>
				<?php
					esc_html_e( $trx['completedTime'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
		</ul>
		<p>
			<?php
			$active = ( $trx['transactionStatus'] ?? '' ) === 'Completed' ? 'button-primary' : 'button';
			?>
			<button
				class="button button-small 
				<?php
				echo esc_attr( $active );
				?>
				">
				Transfer Status -
				<?php
				esc_html_e( $trx['transactionStatus'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
			</button>
		</p>
	</div>
	<?php
}
?>
