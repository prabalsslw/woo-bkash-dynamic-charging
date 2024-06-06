<?php

namespace bKash\PGW\DC\Admin\Module;

use bKash\PGW\DC\Admin\AdminUtility;

class WebhookModule {
	public static function webhooks() {
		AdminUtility::loadTable(
			'bKash Webhook Notifications',
			'bkash_dc_webhooks',
			array(
				'ID'            => 'ID',
				'TRX_ID'        => 'trx_id',
				'SENDER'        => 'sender',
				'RECEIVER'      => 'receiver',
				'RECEIVER NAME' => 'receiver_name',
				'AMOUNT'        => 'amount',
				'REFERENCE'     => 'reference',
				'TYPE'          => 'type',
				'STATUS'        => 'status',
				'DATETIME'      => 'datetime',
			),
			array(
				'trx_id'   => 'Transaction ID',
				'sender' => 'Sender Wallet',
			)
		);
	}
}
