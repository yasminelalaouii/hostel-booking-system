<?php
// Get all bookings for the payment form
global $all_bookings;
// If not defined, fetch it
if (!isset($all_bookings)) {
    $all_bookings = fetchAll("SELECT b.*, u.full_name, r.room_number, r.room_type
                              FROM bookings b
                              JOIN users u ON b.user_id = u.user_id
                              JOIN rooms r ON b.room_id = r.room_id
                              ORDER BY b.created_at DESC");
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Payments Management</h4>
            <button class="btn btn-primary" onclick="showAddPaymentModal()">
                <i class="fas fa-plus"></i> Record New Payment
            </button>
        </div>
        
        <!-- Payment Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bookings">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_payments'] ?? 0; ?></div>
                    <div class="stat-label">Total Payments</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value">$<?php echo number_format($stats['today_revenue'] ?? 0, 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>
        </div>
        
        <!-- Payments Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Get all payments
                        $all_payments = fetchAll("SELECT p.*, b.booking_id, b.total_price, u.full_name 
                                                  FROM payments p
                                                  JOIN bookings b ON p.booking_id = b.booking_id
                                                  JOIN users u ON b.user_id = u.user_id
                                                  ORDER BY p.payment_date DESC");
                        
                        if (empty($all_payments)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h5>No payments recorded yet</h5>
                                <p class="text-muted">Record your first payment using the button above.</p>
                            </td>
                        </tr>
                        <?php else:
                        foreach ($all_payments as $payment): ?>
                        <tr>
                            <td><strong>#<?php echo $payment['PAYMENT_ID']; ?></strong></td>
                            <td>
                                <a href="admin_dashboard.php?action=bookings" class="text-decoration-none">
                                    #<?php echo $payment['BOOKING_ID']; ?>
                                </a><br>
                                <small class="text-muted"><?php echo $payment['FULL_NAME']; ?></small>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($payment['AMOUNT_PAID'], 2); ?></strong><br>
                                <small class="text-muted">Total: $<?php echo number_format($payment['TOTAL_PRICE'], 2); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?php echo ucfirst(str_replace('_', ' ', $payment['PAYMENT_METHOD'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo ucfirst($payment['PAYMENT_TYPE']); ?>
                            </td>
                            <td>
                                <?php echo date('M d, Y', strtotime($payment['PAYMENT_DATE'])); ?><br>
                                <small class="text-muted"><?php echo date('h:i A', strtotime($payment['PAYMENT_DATE'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    <?php echo ucfirst($payment['STATUS']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($payment['TRANSACTION_ID']): ?>
                                    <code><?php echo $payment['TRANSACTION_ID']; ?></code>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($payment['NOTES']): ?>
                                    <small><?php echo htmlspecialchars($payment['NOTES']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">No notes</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record New Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_payment">
                    <input type="hidden" name="redirect" value="payments">
                    
                    <div class="mb-3">
                        <label class="form-label">Booking ID *</label>
                        <select name="booking_id" class="form-select" required id="paymentBookingSelect">
                            <option value="">Select Booking...</option>
                            <?php foreach ($all_bookings as $booking): ?>
                                <option value="<?php echo $booking['BOOKING_ID']; ?>">
                                    #<?php echo $booking['BOOKING_ID']; ?> - 
                                    <?php echo $booking['FULL_NAME']; ?> - 
                                    Room <?php echo $booking['ROOM_NUMBER']; ?> - 
                                    $<?php echo number_format($booking['TOTAL_PRICE'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount ($) *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount" class="form-control" 
                                   step="0.01" min="0.01" required placeholder="0.00"
                                   id="paymentAmount">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method *</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="online">Online Payment</option>
                                <option value="admin_manual" selected>Admin Manual</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Type *</label>
                            <select name="payment_type" class="form-select" required>
                                <option value="booking" selected>Booking Payment</option>
                                <option value="additional">Additional Charges</option>
                                <option value="refund">Refund</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction ID (Optional)</label>
                        <input type="text" name="transaction_id" class="form-control" 
                               placeholder="e.g., TXN123456">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Payment details or remarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                        <i class="fas fa-check"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show modal for adding payment from payments page
function showAddPaymentModal() {
    $('#addPaymentModal').modal('show');
    
    // Focus on amount field
    setTimeout(() => {
        $('#paymentAmount').focus();
    }, 500);
}

// Form validation for payment modal
$('#paymentForm').submit(function(e) {
    const amount = parseFloat($('#paymentAmount').val());
    const bookingId = $('#paymentBookingSelect').val();
    
    if (amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid amount greater than 0');
        return false;
    }
    
    if (!bookingId) {
        e.preventDefault();
        alert('Please select a booking');
        return false;
    }
    
    // Show loading
    $('#submitPaymentBtn').html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    $('#submitPaymentBtn').prop('disabled', true);
    return true;
});
</script>