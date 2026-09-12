<div class="row">
    <div class="col-12">
        <h4 class="mb-4">Users Management</h4>
        
        <!-- Users Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo $user['USER_ID']; ?></td>
                            <td>
                                <strong><?php echo $user['FULL_NAME']; ?></strong>
                            </td>
                            <td><?php echo $user['EMAIL']; ?></td>
                            <td>
                                <span class="badge <?php echo $user['USER_TYPE'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo ucfirst($user['USER_TYPE']); ?>
                                </span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($user['CREATED_AT'])); ?></small>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_user_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                    <input type="hidden" name="redirect" value="users">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                               <?php echo $user['IS_ACTIVE'] == 1 ? 'checked' : ''; ?>
                                               onchange="this.form.submit()">
                                    </div>
                                </form>
                            </td>
                            <td>
                                <?php if ($user['USER_TYPE'] !== 'admin'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['USER_ID']; ?>">
                                    <input type="hidden" name="redirect" value="users">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action"
                                            onclick="return confirm('Delete user <?php echo $user['FULL_NAME']; ?>?')"
                                            title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>