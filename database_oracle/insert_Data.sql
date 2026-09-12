--Insert admin user--

INSERT INTO users (full_name, email, password_hash, user_type)
VALUES (
    'Admin User',
    'admin@hostel.com',
    'hostel123', 
    'admin'
);

--Insert regular users--
INSERT INTO users (full_name, email, password_hash, user_type, phone_number)
VALUES
('John Doe', 'john.doe@email.com', 'hash1', 'user', '0600000001');

INSERT INTO users (full_name, email, password_hash, user_type, phone_number)
VALUES
('Sara Smith', 'sara.smith@email.com', 'hash2', 'user', '0600000002');

--Insert rooms (correct FK handling)--

INSERT INTO rooms (
    room_number,
    room_type,
    description,
    capacity,
    price_per_night,
    amenities,
    created_by
)
VALUES (
    '101',
    'Single',
    'Cozy single room with attached bathroom',
    1,
    50.00,
    'WiFi, TV, AC, Private Bathroom',
    (SELECT user_id FROM users WHERE email = 'admin@hostel.com')
);

INSERT INTO rooms (
    room_number,
    room_type,
    description,
    capacity,
    price_per_night,
    amenities,
    created_by
)
VALUES (
    '102',
    'Double',
    'Spacious room for couples',
    2,
    80.00,
    'WiFi, TV, AC, Private Bathroom, King Bed',
    (SELECT user_id FROM users WHERE email = 'admin@hostel.com')
);

INSERT INTO rooms (
    room_number,
    room_type,
    description,
    capacity,
    price_per_night,
    amenities,
    created_by
)
VALUES (
    '103',
    'Family',
    'Large room for families',
    4,
    120.00,
    'WiFi, 2 TVs, AC, 2 Bathrooms, Kitchenette',
    (SELECT user_id FROM users WHERE email = 'admin@hostel.com')
);

--Insert bookings--
INSERT INTO bookings (
    user_id,
    room_id,
    check_in_date,
    check_out_date,
    total_nights,
    total_price,
    booking_status,
    payment_status
)
VALUES (
    (SELECT user_id FROM users WHERE email = 'john.doe@email.com'),
    (SELECT room_id FROM rooms WHERE room_number = '101'),
    DATE '2025-01-05',
    DATE '2025-01-10',
    5,
    250.00,
    'confirmed',
    'paid'
);

--Insert payment--
INSERT INTO payments (
    booking_id,
    amount_paid,
    payment_method,
    transaction_id,
    status
)
VALUES (
    (SELECT booking_id FROM bookings WHERE booking_status = 'confirmed' FETCH FIRST 1 ROWS ONLY),
    250.00,
    'credit_card',
    'TXN123456',
    'success'
);

--Insert audit log--
INSERT INTO audit_log (
    admin_id,
    action_type,
    table_name,
    record_id,
    new_values,
    ip_address
)
VALUES (
    (SELECT user_id FROM users WHERE email = 'admin@hostel.com'),
    'INSERT',
    'ROOMS',
    (SELECT room_id FROM rooms WHERE room_number = '101'),
    'Room 101 created',
    '127.0.0.1'
);

--Verification queries--
SELECT * FROM users;
SELECT * FROM rooms;
SELECT * FROM bookings;
SELECT * FROM payments;
SELECT * FROM audit_log;

