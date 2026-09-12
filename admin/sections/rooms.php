<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Rooms Management</h4>
            <button class="btn btn-primary" onclick="showAddRoomModal()">
                <i class="fas fa-plus"></i> Add New Room
            </button>
        </div>
        
        <!-- Rooms Table -->
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Room #</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Price/Night</th>
                            <th>Status</th>
                            <th>Amenities</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_rooms as $room): ?>
                        <tr>
                            <td><strong><?php echo $room['ROOM_NUMBER']; ?></strong></td>
                            <td><?php echo $room['ROOM_TYPE']; ?></td>
                            <td><?php echo $room['CAPACITY']; ?> persons</td>
                            <td>$<?php echo number_format($room['PRICE_PER_NIGHT'], 2); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="update_room_status">
                                    <input type="hidden" name="room_id" value="<?php echo $room['ROOM_ID']; ?>">
                                    <input type="hidden" name="redirect" value="rooms">
                                    <select name="status" class="form-select form-select-sm" 
                                            onchange="this.form.submit()" style="width: auto;">
                                        <option value="available" <?php echo $room['STATUS'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="occupied" <?php echo $room['STATUS'] === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="maintenance" <?php echo $room['STATUS'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <small><?php echo substr($room['AMENITIES'], 0, 50); ?>...</small>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($room['CREATED_AT'])); ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-action" 
                                        onclick="showEditRoomModal(<?php echo $room['ROOM_ID']; ?>)"
                                        title="Edit Room">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete_room">
                                    <input type="hidden" name="room_id" value="<?php echo $room['ROOM_ID']; ?>">
                                    <input type="hidden" name="redirect" value="rooms">
                                    <button type="submit" class="btn btn-sm btn-danger btn-action"
                                            onclick="return confirm('Delete room <?php echo $room['ROOM_NUMBER']; ?>? This cannot be undone.')"
                                            title="Delete Room">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    <input type="hidden" name="redirect" value="rooms">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number *</label>
                            <input type="text" name="room_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Single">Single</option>
                                <option value="Double">Double</option>
                                <option value="Family">Family</option>
                                <option value="Suite">Suite</option>
                                <option value="Deluxe">Deluxe</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity *</label>
                            <input type="number" name="capacity" class="form-control" min="1" max="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night ($) *</label>
                            <input type="number" name="price_per_night" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amenities</label>
                        <textarea name="amenities" class="form-control" rows="3" 
                                  placeholder="WiFi, TV, AC, etc..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Initial Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_room">
                    <input type="hidden" name="redirect" value="rooms">
                    <input type="hidden" name="room_id" id="editRoomId">
                    
                    <!-- Form fields same as add room -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Number *</label>
                            <input type="text" name="room_number" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room Type *</label>
                            <select name="room_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="Single">Single</option>
                                <option value="Double">Double</option>
                                <option value="Family">Family</option>
                                <option value="Suite">Suite</option>
                                <option value="Deluxe">Deluxe</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity *</label>
                            <input type="number" name="capacity" class="form-control" min="1" max="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night ($) *</label>
                            <input type="number" name="price_per_night" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amenities</label>
                        <textarea name="amenities" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Under Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>