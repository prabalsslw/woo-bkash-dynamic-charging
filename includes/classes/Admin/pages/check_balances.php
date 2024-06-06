<h1>
<?php
	esc_html_e( get_admin_page_title(), 'woo-bkash-dynamic-charging' );
?>
	</h1>
<?php

use function bKash\PGW\DC\WooCommerceBkashDC;

if ( isset( $balances ) && is_string( $balances ) ) { ?>
	<div id="message" class="woocommerce-message bKash-hero-div bKash-error-div"><p>
			<?php esc_html_e( ( $balances ?? '' ), 'woo-bkash-dynamic-charging' ); ?>
			</p></div>
	<?php
} elseif ( isset( $balances['organizationBalance'] ) && is_array( $balances['organizationBalance'] ) ) {
	// GOT BALANCES.
	foreach ( $balances['organizationBalance'] as $balance ) {
		?>
		<div class="gateway-banner bKash-hero-div bKash-success">
		<img style="max-width: 90px; margin: 10px 5px" alt="bkash logo check balance" src="
		<?php
		echo esc_url( WooCommerceBkashDC()->pluginUrl() . '/assets/images/logo.png' );
		?>
		"/>
		<p class="main"><strong>
		<?php
				esc_html_e( $balance['accountTypeName'] ?? '', 'woo-bkash-dynamic-charging' );
		?>
				</strong></p>
		<hr>
		<p>Current Balance:<b>
		<?php
				esc_html_e(
					( $balance['currentBalance'] ?? '' ) . ' ' . ( $balance['currency'] ?? '' ),
					'woo-bkash-dynamic-charging'
				);
		?>
					</b></p>
		<p>Available Balance:<b>
		<?php
				esc_html_e(
					( $balance['availableBalance'] ?? '' ) . ' ' . ( $balance['currency'] ?? '' ),
					'woo-bkash-dynamic-charging'
				);
		?>
					</b></p>
		<hr>
		<ul>
			<li>Account Enabled?<strong>
			<?php
					esc_html_e( $balance['accountStatus'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
					</strong></li>
			<li>Account Name<strong>
			<?php
					esc_html_e( $balance['accountHolderName'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
					</strong></li>
			<li>Last updated<strong>
			<?php
					esc_html_e( $balance['updateTime'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
					</strong></li>
		</ul>
		<p>
		<?php
			$active = ( $balance['accountStatus'] ?? '' ) === 'Active' ? 'button-primary' : 'button';
		?>
			<button class="button button-small 
			<?php
			echo esc_attr( $active );
			?>
			">
			<?php
				esc_html_e( $balance['accountStatus'] ?? '', 'woo-bkash-dynamic-charging' );
			?>
				</button>
		</p></div><?php
	}
}
?>
