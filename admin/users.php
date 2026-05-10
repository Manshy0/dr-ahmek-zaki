<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_role(['admin']);

$page_title = 'Users';
$page_subtitle = 'Manage who can access the admin panel and their permissions.';

$msg = null; $msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'author';
        $password = $_POST['password'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
            $msg = 'Username must be 3-30 chars: letters, numbers, _ -'; $msg_type = 'error';
        } elseif (db_get_user($username)) {
            $msg = 'Username already exists'; $msg_type = 'error';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $msg = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'; $msg_type = 'error';
        } elseif (!isset(ROLES[$role])) {
            $msg = 'Invalid role'; $msg_type = 'error';
        } else {
            if (db_create_user($username, $password, $name ?: $username, $email, $role)) {
                log_activity('create_user', ['username' => $username, 'role' => $role]);
                $msg = "User '$username' created successfully";
            } else {
                $msg = "Failed to create user"; $msg_type = 'error';
            }
        }
    } elseif ($action === 'update') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $user = db_get_user_by_id($user_id);
        if (!$user) {
            $msg = 'User not found'; $msg_type = 'error';
        } else {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
            ];
            // Don't allow changing your own role
            $current = current_user();
            if ($user['username'] !== $current['username']) {
                $newrole = $_POST['role'] ?? $user['role'];
                if (isset(ROLES[$newrole])) {
                    $data['role'] = $newrole;
                }
            }
            if (!empty($_POST['password'])) {
                if (strlen($_POST['password']) < PASSWORD_MIN_LENGTH) {
                    $msg = 'Password too short'; $msg_type = 'error';
                } else {
                    $data['password'] = $_POST['password'];
                }
            }
            if (!$msg) {
                if (db_update_user($user_id, $data)) {
                    log_activity('update_user', ['username' => $user['username']]);
                    $msg = "User '{$user['username']}' updated";
                } else {
                    $msg = "Failed to update user"; $msg_type = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $user = db_get_user_by_id($user_id);
        $current = current_user();
        if (!$user) {
            $msg = 'User not found'; $msg_type = 'error';
        } elseif ($user['username'] === $current['username']) {
            $msg = "You can't delete your own account"; $msg_type = 'error';
        } elseif ($user['username'] === 'admin') {
            $msg = "Cannot delete the main admin account"; $msg_type = 'error';
        } else {
            if (db_delete_user($user_id)) {
                log_activity('delete_user', ['username' => $user['username']]);
                $msg = "User '{$user['username']}' deleted";
            } else {
                $msg = "Failed to delete user"; $msg_type = 'error';
            }
        }
    }
}

$users = db_get_all_users();
$current = current_user();

include __DIR__ . '/includes/header.php';
?>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= escape($msg) ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header">
    <div class="card-title">Add new user</div>
  </div>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="create">
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" class="form-input" name="username" required pattern="[a-zA-Z0-9_-]{3,30}">
      </div>
      <div class="form-group">
        <label class="form-label">Display name</label>
        <input type="text" class="form-input" name="name">
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" class="form-input" name="email">
      </div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select class="form-select" name="role">
          <?php foreach (ROLES as $r => $desc): ?>
            <option value="<?= escape($r) ?>" <?= $r === 'editor' ? 'selected' : '' ?>><?= escape($desc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-input" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">Create user</button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><?= count($users) ?> users</div>
  </div>
  <div class="table-wrap" style="border:0;">
  <table class="data-table">
    <thead>
      <tr>
        <th>User</th>
        <th>Role</th>
        <th>Last login</th>
        <th>Created</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td>
          <div class="col-title"><?= escape($u['name'] ?? $u['username']) ?> <?php if ($u['username'] === $current['username']): ?><span class="badge badge-success">You</span><?php endif; ?></div>
          <div class="col-meta"><?= escape($u['username']) ?> <?php if (!empty($u['email'])) echo ' · ' . escape($u['email']); ?></div>
        </td>
        <td><span class="badge"><?= escape($u['role']) ?></span></td>
        <td class="col-meta"><?= $u['last_login'] ? relative_time(strtotime($u['last_login'])) : '—' ?></td>
        <td class="col-meta"><?= relative_time(strtotime($u['created_at'])) ?></td>
        <td class="row-actions">
          <button class="btn btn-secondary btn-sm" onclick="editUser(<?= $u['id'] ?>)">Edit</button>
          <?php if ($u['username'] !== $current['username'] && $u['username'] !== 'admin'): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user <?= escape($u['username']) ?>?');">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Delete</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
      <tr id="edit-<?= $u['id'] ?>" style="display:none;">
        <td colspan="5" style="background:#f8fafc; padding:20px;">
          <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Display name</label>
                <input type="text" class="form-input" name="name" value="<?= escape($u['name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" name="email" value="<?= escape($u['email'] ?? '') ?>">
              </div>
              <?php if ($u['username'] !== $current['username']): ?>
              <div class="form-group">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                  <?php foreach (ROLES as $r => $desc): ?>
                    <option value="<?= escape($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= escape($desc) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="form-group">
                <label class="form-label">New password <span class="text-muted">(leave empty to keep)</span></label>
                <input type="password" class="form-input" name="password" minlength="<?= PASSWORD_MIN_LENGTH ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeEdit(<?= $u['id'] ?>)">Cancel</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header">
    <div class="card-title">Role permissions reference</div>
  </div>
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
    <div>
      <strong>Administrator</strong>
      <ul style="margin-top:8px; padding-left:20px; color:var(--text-muted); font-size:13px; line-height:1.8;">
        <li>Full access to everything</li>
        <li>Manage users</li>
        <li>Delete pages &amp; posts</li>
        <li>Edit menu &amp; SEO</li>
      </ul>
    </div>
    <div>
      <strong>Editor</strong>
      <ul style="margin-top:8px; padding-left:20px; color:var(--text-muted); font-size:13px; line-height:1.8;">
        <li>Edit any page or post</li>
        <li>Publish posts</li>
        <li>Upload media</li>
        <li>Edit menu &amp; SEO</li>
      </ul>
    </div>
    <div>
      <strong>Author</strong>
      <ul style="margin-top:8px; padding-left:20px; color:var(--text-muted); font-size:13px; line-height:1.8;">
        <li>Create &amp; publish posts</li>
        <li>Edit own posts</li>
        <li>Upload media</li>
      </ul>
    </div>
    <div>
      <strong>Contributor</strong>
      <ul style="margin-top:8px; padding-left:20px; color:var(--text-muted); font-size:13px; line-height:1.8;">
        <li>Create draft posts</li>
        <li>Edit own drafts</li>
        <li>Cannot publish directly</li>
      </ul>
    </div>
  </div>
</div>

<script>
function editUser(id) {
  const row = document.getElementById('edit-' + id);
  row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
function closeEdit(id) {
  document.getElementById('edit-' + id).style.display = 'none';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
