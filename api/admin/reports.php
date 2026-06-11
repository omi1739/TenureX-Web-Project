<?php
require_once __DIR__ . '/../helpers.php';
requireAuth(['admin']);
$db = getDB();

$totalProperties = (int)$db->query('SELECT COUNT(*) FROM properties WHERE status="active"')->fetchColumn();
$totalUnits      = (int)$db->query('SELECT COUNT(*) FROM units')->fetchColumn();
$occupiedUnits   = (int)$db->query('SELECT COUNT(*) FROM units WHERE is_occupied=1')->fetchColumn();
$totalRevenue    = (float)$db->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status="paid"')->fetchColumn();
$monthlyRevenue  = (float)$db->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status="paid" AND MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())')->fetchColumn();
$pendingApprovals= (int)$db->query('SELECT COUNT(*) FROM users WHERE approval_status="pending"')->fetchColumn();
$openMaintenance = (int)$db->query('SELECT COUNT(*) FROM maintenance_requests WHERE status IN("open","in_progress")')->fetchColumn();
$totalTenants    = (int)$db->query('SELECT COUNT(*) FROM users WHERE role="tenant" AND approval_status="approved"')->fetchColumn();
$totalLandlords  = (int)$db->query('SELECT COUNT(*) FROM users WHERE role="landlord" AND approval_status="approved"')->fetchColumn();

$topProps = $db->query('
    SELECT p.name, p.city,
           COUNT(DISTINCT u.id) AS total_units,
           SUM(u.is_occupied) AS occupied,
           COALESCE(SUM(pay.amount),0) AS revenue
    FROM properties p
    LEFT JOIN units u   ON u.property_id = p.id
    LEFT JOIN leases l  ON l.unit_id = u.id AND l.status="active"
    LEFT JOIN payments pay ON pay.lease_id = l.id AND pay.status="paid"
    GROUP BY p.id
    ORDER BY revenue DESC
    LIMIT 5
')->fetchAll();

$monthlyTrend = $db->query('
    SELECT DATE_FORMAT(due_date,"%Y-%m") AS month,
           COALESCE(SUM(amount),0) AS revenue,
           COUNT(*) AS payments
    FROM payments WHERE status="paid"
    GROUP BY DATE_FORMAT(due_date,"%Y-%m")
    ORDER BY month DESC
    LIMIT 6
')->fetchAll();

success([
    'total_properties'  => $totalProperties,
    'total_units'       => $totalUnits,
    'occupied_units'    => $occupiedUnits,
    'occupancy_rate'    => $totalUnits > 0 ? round($occupiedUnits / $totalUnits * 100, 1) : 0,
    'total_revenue'     => $totalRevenue,
    'monthly_revenue'   => $monthlyRevenue,
    'pending_approvals' => $pendingApprovals,
    'open_maintenance'  => $openMaintenance,
    'total_tenants'     => $totalTenants,
    'total_landlords'   => $totalLandlords,
    'top_properties'    => $topProps,
    'monthly_trend'     => array_reverse($monthlyTrend),
]);
