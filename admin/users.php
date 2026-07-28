<?php
$page_title = 'Users';
require_once 'includes/header.php';

$users_q = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$users = $users_q ? $users_q : false;
?>

<div class="table-card">
  <div class="table-header">
    <h5><i class="bi bi-people-fill me-2" style="color:var(--red);"></i>Registered Users</h5>
    <div class="table-actions">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" placeholder="Search users..." onkeyup="filterTable(this)">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="dataTable">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Joined</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($users && mysqli_num_rows($users) > 0): ?>
          <?php $i = 1; while ($u = mysqli_fetch_assoc($users)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px;">
                  <div style="width:32px;height:32px;border-radius:50%;background:var(--red-gradient);display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;font-weight:700;"><?php echo strtoupper(substr($u['name'], 0, 1)); ?></div>
                  <strong><?php echo $u['name']; ?></strong>
                </div>
              </td>
              <td><?php echo $u['email']; ?></td>
              <td><?php echo $u['phone'] ?? '—'; ?></td>
              <td><small style="color:var(--text-muted);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></small></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-icon"><i class="bi bi-people"></i></div><h6>No users yet</h6><p>Users will appear once they register.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?php echo $users && mysqli_num_rows($users) > 0 ? mysqli_num_rows($users) . ' users' : '0 users'; ?></span>
  </div>
</div>

<script>
function filterTable(input) {
  var val = input.value.toLowerCase();
  var rows = document.querySelectorAll('#dataTable tbody tr');
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = text.indexOf(val) > -1 ? '' : 'none';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
