<?php
/**
 * Webhook compatibility endpoint.
 * The EasyPay gateway is configured to call /webhook.php for payment events.
 * All event handling lives in callback.php.
 */
require_once __DIR__ . '/callback.php';
