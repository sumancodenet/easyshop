<?php
require_once 'includes/config.php';
require_once 'includes/easypay.php';

/**
 * EasyPay webhook + callback endpoint.
 * Receives payment.submitted webhook and signed payment.status callbacks.
 * Returns 200 OK fast. Responds 401 on invalid signature.
 */

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
  exit;
}

$event = $payload['event'] ?? '';
$txn_id = $payload['transactionId'] ?? '';
$order_no = $payload['orderId'] ?? '';

if ($event === 'payment.submitted') {
  // Customer submitted UTR + proof - transaction is now "initiated"
  $utr = isset($payload['utrNumber']) ? mysqli_real_escape_string($conn, $payload['utrNumber']) : '';
  $method = isset($payload['paymentMethod']) ? mysqli_real_escape_string($conn, $payload['paymentMethod']) : '';
  $acct_id = isset($payload['paymentAccountId']) ? (int)$payload['paymentAccountId'] : 0;

  if ($txn_id) {
    $esc_txn = mysqli_real_escape_string($conn, $txn_id);
    $esc_raw = mysqli_real_escape_string($conn, $raw);
    $esc_ord = mysqli_real_escape_string($conn, $order_no);
    // Order is created at checkout.php - find it by order_number to link the transaction
    $oid_sql = 'NULL';
    if ($order_no) {
      $ord = mysqli_query($conn, "SELECT id FROM orders WHERE order_number='$esc_ord' LIMIT 1");
      if ($ord && $orow = mysqli_fetch_assoc($ord)) {
        $oid_sql = (int)$orow['id'];
        // Sync gateway txn id onto the order if it isn't set yet
        mysqli_query($conn, "UPDATE orders SET transaction_id='$esc_txn' WHERE id={$orow['id']} AND (transaction_id='' OR transaction_id IS NULL)");
      }
    }
    $ptx = mysqli_query($conn, "SELECT id FROM payment_transactions WHERE transaction_id='$esc_txn' LIMIT 1");
    if ($ptx && $row = mysqli_fetch_assoc($ptx)) {
      mysqli_query($conn, "UPDATE payment_transactions SET order_id=$oid_sql, order_number='$esc_ord', status='initiated', utr_number='$utr', payment_method='$method', payment_account_id=$acct_id, event='payment.submitted', raw_payload='$esc_raw' WHERE id={$row['id']}");
    } else {
      // Unknown transaction - record it anyway for reconciliation
      mysqli_query($conn, "INSERT INTO payment_transactions (order_id, order_number, transaction_id, status, payment_method, payment_account_id, utr_number, event, raw_payload) VALUES ($oid_sql, '$esc_ord', '$esc_txn', 'initiated', '$method', $acct_id, '$utr', 'payment.submitted', '$esc_raw')");
    }
  }
  http_response_code(200);
  echo json_encode(['success' => true]);
  exit;
}

if ($event === 'payment.status') {
  // Admin approved/rejected in EasyPay dashboard - signed callback
  if (!easypay_verify_signature($payload)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit;
  }

  $status = $payload['status'] ?? '';
  $utr = isset($payload['utrNumber']) ? mysqli_real_escape_string($conn, $payload['utrNumber']) : '';
  $method = isset($payload['paymentMethod']) ? mysqli_real_escape_string($conn, $payload['paymentMethod']) : '';
  $acct_id = isset($payload['paymentAccountId']) ? (int)$payload['paymentAccountId'] : 0;
  $esc_raw = mysqli_real_escape_string($conn, $raw);

  if ($txn_id) {
    $esc_txn = mysqli_real_escape_string($conn, $txn_id);
    $ptx = mysqli_query($conn, "SELECT id FROM payment_transactions WHERE transaction_id='$esc_txn' LIMIT 1");

    if ($status === 'success') {
      // Payment approved - order enters normal processing flow
      // Match by transaction_id AND orderId (SDK uses its own transaction, so
      // the callback txn may differ from the one stored on the order)
      // Guard: sirf 'initiated' orders update hoti hain - cancelled order
      // late webhook se wapas resurrect nahi hogi
      $esc_ord = mysqli_real_escape_string($conn, $order_no);
      mysqli_query($conn, "UPDATE orders SET order_status='pending', payment_status='success', transaction_id='$esc_txn' WHERE (transaction_id='$esc_txn' OR order_number='$esc_ord') AND order_status='initiated'");
      $tx_status = 'success';
    } elseif ($status === 'rejected') {
      // Payment rejected - order cancelled + stock wapas restore
      $esc_ord = mysqli_real_escape_string($conn, $order_no);
      $rq = mysqli_query($conn, "SELECT id FROM orders WHERE (transaction_id='$esc_txn' OR order_number='$esc_ord') AND order_status='initiated'");
      if ($rq) {
        while ($rrow = mysqli_fetch_assoc($rq)) {
          es_restore_order_stock($conn, (int)$rrow['id']);
        }
      }
      mysqli_query($conn, "UPDATE orders SET order_status='rejected', payment_status='rejected', transaction_id='$esc_txn' WHERE (transaction_id='$esc_txn' OR order_number='$esc_ord') AND order_status='initiated'");
      $tx_status = 'rejected';
    } else {
      $tx_status = $status;
    }

    if ($ptx && $row = mysqli_fetch_assoc($ptx)) {
      mysqli_query($conn, "UPDATE payment_transactions SET status='$tx_status', payment_method='$method', payment_account_id=$acct_id, utr_number='$utr', event='payment.status', raw_payload='$esc_raw' WHERE id={$row['id']}");
    } else {
      $esc_ord = mysqli_real_escape_string($conn, $order_no);
      $oid_sql = 'NULL';
      if ($order_no) {
        $ord = mysqli_query($conn, "SELECT id FROM orders WHERE order_number='$esc_ord' LIMIT 1");
        if ($ord && $orow = mysqli_fetch_assoc($ord)) $oid_sql = (int)$orow['id'];
      }
      $amt = isset($payload['amount']) ? (float)$payload['amount'] : 0;
      $cur = isset($payload['currency']) ? mysqli_real_escape_string($conn, $payload['currency']) : 'INR';
      $cn = isset($payload['customerName']) ? mysqli_real_escape_string($conn, $payload['customerName']) : '';
      $ce = isset($payload['customerEmail']) ? mysqli_real_escape_string($conn, $payload['customerEmail']) : '';
      $cp = isset($payload['customerPhone']) ? mysqli_real_escape_string($conn, $payload['customerPhone']) : '';
      mysqli_query($conn, "INSERT INTO payment_transactions (order_id, order_number, transaction_id, amount, currency, status, payment_method, payment_account_id, utr_number, customer_name, customer_email, customer_phone, event, raw_payload) VALUES ($oid_sql, '$esc_ord', '$esc_txn', $amt, '$cur', '$tx_status', '$method', $acct_id, '$utr', '$cn', '$ce', '$cp', 'payment.status', '$esc_raw')");
    }
  }

  http_response_code(200);
  echo json_encode(['success' => true]);
  exit;
}

http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Event ignored']);
