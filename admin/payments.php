<?php
$page_title = 'Payments';
require_once 'includes/header.php';

$q = mysqli_query($conn, "SELECT * FROM payment_transactions ORDER BY id DESC");
$txs = $q ? $q : false;

function es_pay_badge($s) {
  switch ($s) {
    case 'success':  return 'bg-success';
    case 'rejected': return 'bg-danger';
    case 'initiated': return 'bg-primary';
    case 'pending':  return 'bg-warning';
    default:         return 'bg-secondary';
  }
}
?>

<div class="table-card">
  <div class="table-header">
    <h5><i class="bi bi-credit-card me-2" style="color:var(--red);"></i>Payment Transactions</h5>
    <div class="table-actions">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" placeholder="Search transactions..." onkeyup="filterTable(this)">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="dataTable">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Transaction ID</th>
          <th>Amount</th>
          <th>Method</th>
          <th>UTR</th>
          <th>Status</th>
          <th>Customer</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($txs && mysqli_num_rows($txs) > 0): ?>
          <?php while ($t = mysqli_fetch_assoc($txs)): ?>
            <tr>
              <td><strong>#<?php echo htmlspecialchars($t['order_number'] ?? '-'); ?></strong></td>
              <td><code><?php echo htmlspecialchars($t['transaction_id'] ?? '-'); ?></code></td>
              <td><strong>₹<?php echo number_format($t['amount']); ?></strong></td>
              <td><?php echo $t['payment_method'] ? strtoupper($t['payment_method']) : '-'; ?></td>
              <td><?php echo htmlspecialchars($t['utr_number'] ?? '-'); ?></td>
              <td>
                <span class="badge-status <?php echo es_pay_badge($t['status']); ?>">
                  <span class="dot"></span> <?php echo ucfirst($t['status']); ?>
                </span>
              </td>
              <td>
                <?php if ($t['customer_name']): ?>
                  <strong><?php echo htmlspecialchars($t['customer_name']); ?></strong><br>
                  <small style="color:var(--text-muted);"><?php echo htmlspecialchars($t['customer_phone'] ?? ''); ?></small>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td><small style="color:var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></small></td>
            </tr>
            <tr class="collapse" id="pay<?php echo $t['id']; ?>">
              <td colspan="8" class="order-detail-row">
                <div class="row g-4">
                  <div class="col-md-6">
                    <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:12px;">Transaction Details</h6>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
                      <span style="color:var(--text-muted);">Transaction ID</span><span><strong><?php echo htmlspecialchars($t['transaction_id'] ?? '-'); ?></strong></span>
                      <span style="color:var(--text-muted);">Order</span><span><strong>#<?php echo htmlspecialchars($t['order_number'] ?? '-'); ?></strong></span>
                      <span style="color:var(--text-muted);">Amount</span><span><strong>₹<?php echo number_format($t['amount']); ?> <?php echo $t['currency']; ?></strong></span>
                      <span style="color:var(--text-muted);">Method</span><span><?php echo $t['payment_method'] ? strtoupper($t['payment_method']) : '-'; ?></span>
                      <span style="color:var(--text-muted);">Account</span><span><?php echo htmlspecialchars($t['payment_account_name'] ?? '-'); ?></span>
                      <span style="color:var(--text-muted);">UTR</span><span><?php echo htmlspecialchars($t['utr_number'] ?? '-'); ?></span>
                      <span style="color:var(--text-muted);">Status</span><span><?php echo ucfirst($t['status']); ?></span>
                      <span style="color:var(--text-muted);">Event</span><span><?php echo htmlspecialchars($t['event'] ?? '-'); ?></span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:12px;">Customer</h6>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
                      <span style="color:var(--text-muted);">Name</span><span><strong><?php echo htmlspecialchars($t['customer_name'] ?? '-'); ?></strong></span>
                      <span style="color:var(--text-muted);">Email</span><span><?php echo htmlspecialchars($t['customer_email'] ?? '-'); ?></span>
                      <span style="color:var(--text-muted);">Phone</span><span><?php echo htmlspecialchars($t['customer_phone'] ?? '-'); ?></span>
                      <span style="color:var(--text-muted);">Created</span><span><?php echo date('d M Y, h:i A', strtotime($t['created_at'])); ?></span>
                      <span style="color:var(--text-muted);">Updated</span><span><?php echo date('d M Y, h:i A', strtotime($t['updated_at'])); ?></span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="bi bi-credit-card"></i></div><h6>No transactions yet</h6><p>Online payment transactions will appear here.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?php echo $txs && mysqli_num_rows($txs) > 0 ? mysqli_num_rows($txs) . ' transactions' : '0 transactions'; ?></span>
  </div>
</div>

<script>
function filterTable(input) {
  var val = input.value.toLowerCase();
  var rows = document.querySelectorAll('#dataTable tbody tr:not(.collapse)');
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = text.indexOf(val) > -1 ? '' : 'none';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
