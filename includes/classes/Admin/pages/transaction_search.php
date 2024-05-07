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
	esc_attr_e( get_admin_page_title(), 'woo-bkash-dynamic-charging' );
?>
	</h1>
<br>
<form action="#" method="post">
	<label for="trxid" class="form-label">Transaction ID</label>
	<input name="trxid" type="text" id="trxid" placeholder="Transaction ID" class="form-text-input" autocomplete="off" value="<?php esc_attr_e( $trx_id ?? '', 'woo-bkash-dynamic-charging' ); ?>">

	<button class="button button-primary" type="submit">Search</button>
</form>
<br>

<?php

if ( isset( $trx ) && is_string( $trx ) ) {
	// FAILED TO GET BALANCES
	?>
	<div id="message" class="bKash-hero-div woocommerce-message bKash-error">
		<p>
		<?php
			esc_html_e( $trx ?? '', 'woo-bkash-dynamic-charging' );
		?>
			</p>
	</div>
	<?php
} elseif ( isset( $trx['trxID'] ) && is_array( $trx ) ) {
	// GOT TRANSACTION
	?>
	<div class="gateway-banner bKash-hero-div bKash-success">
		<img style="max-width: 90px; margin: 10px 5px" alt="bKash logo transaction search" src="<?php echo esc_url( WooCommerceBkashDC()->pluginUrl() . '/assets/images/logo.png' ); ?>"/>
		<p class="main">
			<strong>Transaction ID: 
			<?php
				esc_html_e( $trx['trxID'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
				</strong>
		</p>
		<hr>
		<p>Sender: <b>
		<?php
				esc_html_e( $trx['customerMsisdn'] ?? '', 'woo-bkash-dynamic-charging' );
		?>
				</b></p>
		<p>
			Amount:
			<b>
				<?php
				esc_html_e(
					( $trx['amount'] ?? '' ) . ' ' . ( $trx['currency'] ?? '' ),
					'woo-bkash-dynamic-charging'
				);
				?>
			</b>
		</p>
		<hr>
		<ul>
			<li>
				Invoice Number:
				<strong>
				<?php
					esc_html_e( $trx['merchantInvoiceNumber'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
			<li>
				Transaction Type:
				<strong>
				<?php
					esc_html_e( $trx['transactionType'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
			<li>
				Merchant Account:
				<strong>
				<?php
					esc_html_e( $trx['organizationShortCode'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
			<li>
				Service Fee:
				<strong>
				<?php
					esc_html_e( $trx['serviceFee'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
					</strong>
			</li>
			<li>
				Initiated At:
				<strong>
				<?php
					esc_html_e( $trx['initiationTime'] ?? '', 'woo-bkash-dynamic-charging' );
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
			$btn_class = isset( $trx['transactionStatus'] ) && $trx['transactionStatus'] === 'Completed' ? 'button-primary' : 'button';
			?>
			<button class="button button-small 
			<?php
			esc_attr_e( $btn_class, 'woo-bkash-dynamic-charging' );
			?>
			">
				Transaction Status -
				<?php
				esc_html_e( $trx['transactionStatus'] ?? '', 'woo-bkash-dynamic-charging' );
				?>
			</button>
		</p>
	</div>
	<?php
}
?>
