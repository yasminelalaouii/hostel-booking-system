<?php
// Get monthly revenue
$monthly_revenue = fetchAll("SELECT TO_CHAR(b.created_at, 'YYYY-MM') as month,
                                    SUM(b.total_price) as revenue,
                                    COUNT(*) as bookings
                             FROM bookings b
                             WHERE b.booking_status = 'confirmed'
                             GROUP BY TO_CHAR(b.created_at, 'YYYY-MM')
                             ORDER BY month DESC
                             FETCH FIRST 6 ROWS ONLY");

// Get room type statistics
$room_stats = fetchAll("SELECT r.room_type, 
                               COUNT(*) as total_rooms,
                               SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available,
                               SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                               SUM(CASE WHEN r.status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
                        FROM rooms r
                        GROUP BY r.room_type");
?>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">Monthly Revenue</h5>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="chart-container">
            <h5 class="mb-3">Room Statistics</h5>
            <canvas id="roomChart" height="200"></canvas>
        </div>
    </div>
    
    <div class="col-12 mb-4">
        <div class="data-table">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                            <th>Bookings</th>
                            <th>Avg. Booking Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_revenue as $month): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($month['MONTH'] . '-01')); ?></td>
                            <td><strong>$<?php echo number_format($month['REVENUE'], 2); ?></strong></td>
                            <td><?php echo $month['BOOKINGS']; ?></td>
                            <td>$<?php echo number_format($month['REVENUE'] / $month['BOOKINGS'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: [<?php foreach ($monthly_revenue as $m) echo '"' . date('M Y', strtotime($m['MONTH'] . '-01')) . '",'; ?>],
            datasets: [{
                label: 'Revenue ($)',
                data: [<?php foreach ($monthly_revenue as $m) echo $m['REVENUE'] . ','; ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });
    
    // Room Chart
    const roomCtx = document.getElementById('roomChart').getContext('2d');
    const roomChart = new Chart(roomCtx, {
        type: 'pie',
        data: {
            labels: ['Available', 'Occupied', 'Maintenance'],
            datasets: [{
                data: [
                    <?php echo $stats['available_rooms']; ?>,
                    <?php echo $stats['occupied_rooms']; ?>,
                    <?php 
                        $total_rooms = fetchOne("SELECT COUNT(*) as count FROM rooms");
                        echo ($total_rooms['COUNT'] - $stats['available_rooms'] - $stats['occupied_rooms']);
                    ?>
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(255, 99, 132, 0.5)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
</script>