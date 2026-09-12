<?php
// Get all bookings for display
$all_bookings = fetchAll("SELECT b.*, u.full_name, r.room_number, r.room_type
                          FROM bookings b
                          JOIN users u ON b.user_id = u.user_id
                          JOIN rooms r ON b.room_id = r.room_id
                          ORDER BY b.created_at DESC");
?>
<div class="row">
    <div class="col-12">
        <h4 class="mb-4">Bookings Management</h4>
        
        <!-- Bookings Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>Nights</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_bookings as $booking): ?>
                        <tr>
                            <td><strong>#<?php echo $booking['BOOKING_ID']; ?></strong></td>
                            <td>
                                <?php echo $booking['FULL_NAME']; ?><br>
                                <small class="text-muted">ID: <?php echo $booking['USER_ID']; ?></small>
                            </td>
                            <td>
                                <?php echo $booking['ROOM_NUMBER']; ?><br>
                                <small class="text-muted"><?php echo $booking['ROOM_TYPE']; ?></small>
                            </td>
                            <td>
                                <?php echo date('M d', strtotime($booking['CHECK_IN_DATE'])); ?> - <br>
                                <?php echo date('M d', strtotime($booking['CHECK_OUT_DATE'])); ?>
                            </td>
                            <td><?php echo $booking['TOTAL_NIGHTS']; ?></td>
                            <td>$<?php echo number_format($booking['TOTAL_PRICE'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo strtolower($booking['BOOKING_STATUS']); ?>">
                                    <?php echo ucfirst($booking['BOOKING_STATUS']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo strtolower($booking['PAYMENT_STATUS']); ?>">
                                    <?php echo ucfirst($booking['PAYMENT_STATUS']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['BOOKING_STATUS'] === 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="confirm_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['BOOKING_ID']; ?>">
                                        <input type="hidden" name="redirect" value="bookings">
                                        <button type="submit" class="btn btn-sm btn-success btn-action" 
                                                title="Confirm Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['BOOKING_ID']; ?>">
                                        <input type="hidden" name="redirect" value="bookings">
                                        <button type="submit" class="btn btn-sm btn-danger btn-action"
                                                onclick="return confirm('Cancel this booking?')"
                                                title="Cancel Booking">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <button class="btn btn-sm btn-info btn-action" 
                                        onclick="showManageBookingModal(<?php echo $booking['BOOKING_ID']; ?>)"
                                        title="Manage Booking">
                                    <i class="fas fa-cog"></i>
                                </button>
                                
                                <?php if ($booking['PAYMENT_STATUS'] !== 'paid'): ?>
                                <button class="btn btn-sm btn-warning btn-action" 
                                        onclick="showAddPaymentModal(<?php echo $booking['BOOKING_ID']; ?>)"
                                        title="Add Payment">
                                    <i class="fas fa-credit-card"></i>
                                </button>
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


<!-- Manage Booking Modal -->
<div class="modal fade" id="manageBookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="booking_id" id="manageBookingId">
                    <input type="hidden" name="redirect" value="bookings">
                    
                    <div class="mb-3">
                        <label class="form-label">Booking Status</label>
                        <select name="booking_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select" required>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partially Paid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_payment">
                    <input type="hidden" name="redirect" value="bookings"> <!-- Keep redirect to bookings -->
                    <input type="hidden" name="booking_id" id="addPaymentBookingId">
                    
                    <div class="mb-3">
                        <label class="form-label">Booking ID *</label>
                        <input type="text" class="form-control" id="addPaymentBookingDisplay" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount ($) *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="amount" class="form-control" 
                                   step="0.01" min="0.01" required placeholder="0.00"
                                   id="paymentAmountBooking">
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
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtnBooking">
                        <i class="fas fa-check"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show modal for managing booking
function showManageBookingModal(bookingId) {
    $('#manageBookingId').val(bookingId);
    $('#manageBookingModal').modal('show');
}

// Show modal for adding payment
function showAddPaymentModal(bookingId) {
    $('#addPaymentBookingId').val(bookingId);
    $('#addPaymentBookingDisplay').val('Booking #' + bookingId);
    $('#addPaymentModal').modal('show');
    
    // Focus on amount field
    setTimeout(() => {
        $('#paymentAmountBooking').focus();
    }, 500);
}

// Form validation for payment modal
$('#paymentForm').submit(function(e) {
    const amount = parseFloat($('#paymentAmountBooking').val());
    if (amount <= 0) {
        e.preventDefault();
        alert('Please enter a valid amount greater than 0');
        return false;
    }
    
    const bookingId = $('#addPaymentBookingId').val();
    if (!bookingId) {
        e.preventDefault();
        alert('Please select a booking');
        return false;
    }
    
    // Show loading
    $('#submitPaymentBtnBooking').html('<i class="fas fa-spinner fa-spin"></i> Processing...');
    $('#submitPaymentBtnBooking').prop('disabled', true);
});
</script>