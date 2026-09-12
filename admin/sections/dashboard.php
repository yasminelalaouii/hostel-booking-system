<div class="row">
    <!-- Quick Stats -->
    <div class="col-12 mb-4">
        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            
            <div class="stat-card bookings">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            
            <div class="stat-card users">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card rooms">
                <div class="stat-icon">
                    <i class="fas fa-bed"></i>
                </div>
                <div class="stat-value"><?php echo $stats['available_rooms']; ?>/<?php echo $stats['available_rooms'] + $stats['occupied_rooms']; ?></div>
                <div class="stat-label">Rooms Available</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-12 mb-4">
        <h4 class="mb-3">Quick Actions</h4>
        <div class="quick-actions">
            <a href="admin_dashboard.php?action=rooms" class="action-card text-decoration-none">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <h6>Add New Room</h6>
                <small class="text-muted">Add a new room to inventory</small>
            </a>
            
            <a href="admin_dashboard.php?action=bookings" class="action-card text-decoration-none">
                <div class="action-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h6>Manage Bookings</h6>
                <small class="text-muted">View and manage all bookings</small>
            </a>
            
            <a href="#" class="action-card text-decoration-none" onclick="showAddPaymentModal()">
                <div class="action-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h6>Record Payment</h6>
                <small class="text-muted">Record manual payment</small>
            </a>
            
            <a href="admin_dashboard.php?action=users" class="action-card text-decoration-none">
                <div class="action-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h6>Manage Users</h6>
                <small class="text-muted">View and manage user accounts</small>
            </a>
        </div>
    </div>
    
    <!-- Pending Bookings -->
    <div class="col-md-6 mb-4">
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th colspan="4" class="bg-light">
                                <i class="fas fa-clock text-warning me-2"></i>
                                Pending Bookings (<?php echo count($pending_bookings); ?>)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_bookings)): ?>
                            <?php foreach ($pending_bookings as $booking): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo $booking['BOOKING_ID']; ?></strong><br>
                                    <small class="text-muted"><?php echo $booking['FULL_NAME']; ?></small>
                                </td>
                                <td>
                                    Room <?php echo $booking['ROOM_NUMBER']; ?><br>
                                    <small><?php echo date('M d', strtotime($booking['CHECK_IN_DATE'])); ?> - <?php echo date('M d', strtotime($booking['CHECK_OUT_DATE'])); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-pending">Pending</span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="confirm_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['BOOKING_ID']; ?>">
                                        <input type="hidden" name="redirect" value="dashboard">
                                        <button type="submit" class="btn btn-sm btn-success btn-action" 
                                                title="Confirm Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['BOOKING_ID']; ?>">
                                        <input type="hidden" name="redirect" value="dashboard">
                                        <button type="submit" class="btn btn-sm btn-danger btn-action" 
                                                onclick="return confirm('Are you sure you want to cancel this booking?')"
                                                title="Cancel Booking">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                    No pending bookings
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-md-6 mb-4">
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th colspan="3" class="bg-light">
                                <i class="fas fa-history me-2"></i>
                                Recent Activities
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($activity['CREATED_AT'])); ?></small><br>
                                    <?php echo $activity['ACTION_TYPE']; ?>
                                </td>
                                <td>
                                    <small><?php echo $activity['TABLE_NAME']; ?></small><br>
                                    <?php echo substr($activity['NEW_VALUES'], 0, 50); ?>...
                                </td>
                                <td class="text-end">
                                    <small class="text-muted"><?php echo $activity['IP_ADDRESS']; ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                    No activities yet
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>