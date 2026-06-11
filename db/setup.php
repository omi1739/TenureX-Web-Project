<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TenureX — Database Setup</title>
<style>
  body{font-family:system-ui,sans-serif;max-width:720px;margin:60px auto;padding:0 24px;background:#f9f9f9}
  h1{color:#111}pre{background:#111;color:#0f0;padding:16px;border-radius:8px;overflow-x:auto;font-size:13px}
  .btn{display:inline-block;padding:12px 28px;background:#111;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:15px;text-decoration:none}
  .ok{color:#16a34a;font-weight:600}.err{color:#dc2626;font-weight:600}
  .step{padding:6px 0;border-bottom:1px solid #e5e7eb}
</style>
</head>
<body>
<?php
$confirmed = ($_GET['confirm'] ?? '') === 'yes';

if (!$confirmed) {
    echo '<h1>TenureX Database Setup</h1>';
    echo '<p>This script will <strong>drop and recreate</strong> the <code>tenurex</code> database and insert seed data.</p>';
    echo '<p><strong>Test accounts (all passwords below):</strong></p><ul>
    <li>Admin: <code>admin@tenurex.dev</code> / <code>admin123</code></li>
    <li>Landlord (approved): <code>arthur@tenurex.dev</code> / <code>landlord123</code></li>
    <li>Tenant: <code>alice@tenurex.dev</code> / <code>tenant123</code></li>
    <li>Technician: <code>karim@tenurex.dev</code> / <code>tech123</code></li>
    </ul>';
    echo '<a class="btn" href="?confirm=yes">Run Setup Now</a>';
    exit;
}

$steps = [];
function step(string $msg, bool $ok = true): void {
    global $steps;
    $steps[] = [$msg, $ok];
    echo '<div class="step"><span class="' . ($ok ? 'ok' : 'err') . '">' . ($ok ? '✓' : '✗') . '</span> ' . htmlspecialchars($msg) . '</div>';
    flush();
}

echo '<h1>TenureX — Running Setup…</h1>';

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    step('Connected to MySQL');

    $pdo->exec('DROP DATABASE IF EXISTS tenurex');
    $pdo->exec('CREATE DATABASE tenurex DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE tenurex');
    step('Created database `tenurex`');

    // ── TABLES ──────────────────────────────────────────────────────────────

    $pdo->exec("CREATE TABLE users (
      id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      role            ENUM('landlord','tenant','technician','admin') NOT NULL DEFAULT 'tenant',
      full_name       VARCHAR(120) NOT NULL,
      email           VARCHAR(190) NOT NULL UNIQUE,
      phone           VARCHAR(40),
      password_hash   VARCHAR(255) NOT NULL,
      avatar_url      VARCHAR(500),
      approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
      is_active       BOOLEAN NOT NULL DEFAULT TRUE,
      created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    step('Table: users');

    $pdo->exec("CREATE TABLE service_categories (
      id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name        VARCHAR(100) NOT NULL,
      description TEXT,
      icon        VARCHAR(50) DEFAULT 'wrench',
      is_active   BOOLEAN NOT NULL DEFAULT TRUE,
      created_by  INT UNSIGNED,
      created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    step('Table: service_categories');

    $pdo->exec("CREATE TABLE properties (
      id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      owner_id        INT UNSIGNED NOT NULL,
      name            VARCHAR(200) NOT NULL,
      address_line    VARCHAR(300) NOT NULL,
      city            VARCHAR(100) NOT NULL,
      region          VARCHAR(100),
      country         VARCHAR(80) DEFAULT 'Bangladesh',
      postcode        VARCHAR(20),
      cover_image_url VARCHAR(500),
      total_units     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
      monthly_value   DECIMAL(12,2) NOT NULL DEFAULT 0,
      status          ENUM('active','draft','archived') NOT NULL DEFAULT 'active',
      created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: properties');

    $pdo->exec("CREATE TABLE units (
      id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      property_id   INT UNSIGNED NOT NULL,
      label         VARCHAR(60) NOT NULL,
      unit_type     ENUM('apartment','studio','room','office') DEFAULT 'apartment',
      bedrooms      TINYINT UNSIGNED DEFAULT 1,
      bathrooms     TINYINT UNSIGNED DEFAULT 1,
      area_sqft     DECIMAL(8,2),
      monthly_rent  DECIMAL(12,2) NOT NULL DEFAULT 0,
      is_occupied   BOOLEAN NOT NULL DEFAULT FALSE,
      FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: units');

    $pdo->exec("CREATE TABLE amenities (
      id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB");
    step('Table: amenities');

    $pdo->exec("CREATE TABLE unit_amenities (
      unit_id    INT UNSIGNED NOT NULL,
      amenity_id INT UNSIGNED NOT NULL,
      PRIMARY KEY(unit_id, amenity_id),
      FOREIGN KEY (unit_id)    REFERENCES units(id)    ON DELETE CASCADE,
      FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: unit_amenities');

    $pdo->exec("CREATE TABLE rental_requests (
      id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      unit_id          INT UNSIGNED NOT NULL,
      applicant_name   VARCHAR(120) NOT NULL,
      applicant_email  VARCHAR(190) NOT NULL,
      applicant_phone  VARCHAR(40),
      occupation       VARCHAR(120),
      profile_summary  TEXT,
      credit_score     SMALLINT UNSIGNED,
      income_ratio     DECIMAL(5,2),
      requested_move_in DATE,
      lease_months     TINYINT UNSIGNED DEFAULT 12,
      status           ENUM('pending','in_review','accepted','rejected') NOT NULL DEFAULT 'pending',
      applied_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      decided_at       DATETIME,
      FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: rental_requests');

    $pdo->exec("CREATE TABLE leases (
      id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      unit_id           INT UNSIGNED NOT NULL,
      tenant_user_id    INT UNSIGNED NOT NULL,
      rental_request_id INT UNSIGNED,
      start_date        DATE NOT NULL,
      end_date          DATE NOT NULL,
      monthly_rent      DECIMAL(12,2) NOT NULL,
      security_deposit  DECIMAL(12,2) DEFAULT 0,
      status            ENUM('active','ended','terminated') NOT NULL DEFAULT 'active',
      created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (unit_id)        REFERENCES units(id)          ON DELETE CASCADE,
      FOREIGN KEY (tenant_user_id) REFERENCES users(id)          ON DELETE CASCADE,
      FOREIGN KEY (rental_request_id) REFERENCES rental_requests(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    step('Table: leases');

    $pdo->exec("CREATE TABLE payments (
      id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      lease_id  INT UNSIGNED NOT NULL,
      amount    DECIMAL(12,2) NOT NULL,
      due_date  DATE NOT NULL,
      paid_at   DATETIME,
      method    ENUM('bank_transfer','card','cash','other') DEFAULT 'card',
      reference VARCHAR(80),
      status    ENUM('upcoming','paid','overdue','failed') NOT NULL DEFAULT 'upcoming',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (lease_id) REFERENCES leases(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: payments');

    $pdo->exec("CREATE TABLE maintenance_requests (
      id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      property_id   INT UNSIGNED NOT NULL,
      unit_id       INT UNSIGNED,
      reported_by   INT UNSIGNED,
      reporter_name VARCHAR(120),
      reporter_email VARCHAR(190),
      title         VARCHAR(200) NOT NULL,
      description   TEXT,
      category      ENUM('hvac','plumbing','electrical','appliance','structural','other') NOT NULL DEFAULT 'other',
      priority      ENUM('low','medium','urgent') NOT NULL DEFAULT 'medium',
      status        ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
      reported_on   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      resolved_on   DATETIME,
      FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
      FOREIGN KEY (unit_id)     REFERENCES units(id)      ON DELETE SET NULL,
      FOREIGN KEY (reported_by) REFERENCES users(id)      ON DELETE SET NULL
    ) ENGINE=InnoDB");
    step('Table: maintenance_requests');

    $pdo->exec("CREATE TABLE maintenance_photos (
      id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      maintenance_id INT UNSIGNED NOT NULL,
      photo_url      VARCHAR(500) NOT NULL,
      uploaded_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (maintenance_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: maintenance_photos');

    $pdo->exec("CREATE TABLE technicians (
      id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id     INT UNSIGNED UNIQUE,
      full_name   VARCHAR(120) NOT NULL,
      email       VARCHAR(190),
      phone       VARCHAR(40),
      specialty   VARCHAR(120),
      hourly_rate DECIMAL(8,2),
      is_on_call  BOOLEAN NOT NULL DEFAULT FALSE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    step('Table: technicians');

    $pdo->exec("CREATE TABLE technician_requests (
      id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      maintenance_id INT UNSIGNED NOT NULL,
      technician_id  INT UNSIGNED NOT NULL,
      scheduled_for  DATETIME,
      notes          TEXT,
      cost_estimate  DECIMAL(10,2),
      status         ENUM('pending','dispatched','completed','cancelled') NOT NULL DEFAULT 'pending',
      created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (maintenance_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
      FOREIGN KEY (technician_id)  REFERENCES technicians(id)          ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: technician_requests');

    $pdo->exec("CREATE TABLE landlord_tech_requests (
      id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      landlord_id      INT UNSIGNED NOT NULL,
      full_name        VARCHAR(120) NOT NULL,
      phone            VARCHAR(40),
      specialization   VARCHAR(120),
      nid              VARCHAR(80),
      years_experience TINYINT UNSIGNED DEFAULT 0,
      note             TEXT,
      status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
      admin_note       TEXT,
      submitted_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      decided_at       DATETIME,
      FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: landlord_tech_requests');

    $pdo->exec("CREATE TABLE message_threads (
      id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      subject         VARCHAR(200) NOT NULL DEFAULT 'Conversation',
      last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    step('Table: message_threads');

    $pdo->exec("CREATE TABLE message_thread_participants (
      thread_id INT UNSIGNED NOT NULL,
      user_id   INT UNSIGNED NOT NULL,
      joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY(thread_id, user_id),
      FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id)   REFERENCES users(id)           ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: message_thread_participants');

    $pdo->exec("CREATE TABLE messages (
      id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      thread_id INT UNSIGNED NOT NULL,
      sender_id INT UNSIGNED NOT NULL,
      body      TEXT NOT NULL,
      sent_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      read_at   DATETIME,
      FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE,
      FOREIGN KEY (sender_id) REFERENCES users(id)           ON DELETE CASCADE
    ) ENGINE=InnoDB");
    step('Table: messages');

    // ── SEED DATA ───────────────────────────────────────────────────────────

    $adminHash    = password_hash('admin123',    PASSWORD_BCRYPT);
    $landlordHash = password_hash('landlord123', PASSWORD_BCRYPT);
    $tenantHash   = password_hash('tenant123',   PASSWORD_BCRYPT);
    $techHash     = password_hash('tech123',     PASSWORD_BCRYPT);

    $ins = $pdo->prepare('INSERT INTO users (role, full_name, email, phone, password_hash, approval_status) VALUES (?,?,?,?,?,?)');
    $ins->execute(['admin',      'System Admin',     'admin@tenurex.dev',  '+880-1700-000001', $adminHash,    'approved']);
    $adminId = $pdo->lastInsertId();
    $ins->execute(['landlord',   'Arthur Rahman',    'arthur@tenurex.dev', '+880-1700-000002', $landlordHash, 'approved']);
    $landlordId = $pdo->lastInsertId();
    $ins->execute(['landlord',   'Bob Hossain',      'bob@tenurex.dev',    '+880-1700-000003', $landlordHash, 'pending']);
    $ins->execute(['tenant',     'Alice Akter',      'alice@tenurex.dev',  '+880-1700-000004', $tenantHash,   'approved']);
    $tenantId = $pdo->lastInsertId();
    $ins->execute(['tenant',     'Mike Siddiqui',    'mike@tenurex.dev',   '+880-1700-000005', $tenantHash,   'approved']);
    $tenant2Id = $pdo->lastInsertId();
    $ins->execute(['technician', 'Karim Electrician','karim@tenurex.dev',  '+880-1700-000006', $techHash,     'approved']);
    $techUserId = $pdo->lastInsertId();
    step('Seeded: users (6 accounts)');

    $pdo->prepare('INSERT INTO technicians (user_id, full_name, email, phone, specialty, hourly_rate, is_on_call) VALUES (?,?,?,?,?,?,?)')->execute([$techUserId,'Karim Electrician','karim@tenurex.dev','+880-1700-000006','Electrical',800,1]);
    $techId = $pdo->lastInsertId();
    step('Seeded: technicians');

    $insCat = $pdo->prepare('INSERT INTO service_categories (name, description, icon, created_by) VALUES (?,?,?,?)');
    $insCat->execute(['Plumbing',   'Water pipes, drains, taps and related issues',          'droplet',   $adminId]);
    $insCat->execute(['Electrical', 'Wiring, fuses, sockets and power-related issues',       'zap',       $adminId]);
    $insCat->execute(['HVAC',       'Air conditioning, heating, and ventilation systems',    'wind',      $adminId]);
    $insCat->execute(['Appliances', 'Refrigerator, washing machine, stove, and other items','cpu',       $adminId]);
    $insCat->execute(['Structural', 'Walls, roof, flooring, windows and structural damage',  'home',      $adminId]);
    step('Seeded: service_categories (5)');

    $insProp = $pdo->prepare('INSERT INTO properties (owner_id, name, address_line, city, monthly_value, total_units, status) VALUES (?,?,?,?,?,?,?)');
    $insProp->execute([$landlordId, 'Skyline Residences',  '12 Gulshan Avenue',       'Dhaka',     25000, 4, 'active']);
    $propId1 = $pdo->lastInsertId();
    $insProp->execute([$landlordId, 'Green Valley Flats',  '55 Dhanmondi Road 7',     'Dhaka',     18000, 3, 'active']);
    $propId2 = $pdo->lastInsertId();
    $insProp->execute([$landlordId, 'Harbor View Suites',  '8 CDA Avenue',            'Chittagong', 22000, 2, 'active']);
    $propId3 = $pdo->lastInsertId();
    step('Seeded: properties (3)');

    $insUnit = $pdo->prepare('INSERT INTO units (property_id, label, unit_type, bedrooms, bathrooms, area_sqft, monthly_rent, is_occupied) VALUES (?,?,?,?,?,?,?,?)');
    $insUnit->execute([$propId1,'Unit A-101','apartment',2,1,850, 12000,1]); $unitOcc = $pdo->lastInsertId();
    $insUnit->execute([$propId1,'Unit A-102','apartment',3,2,1100,16000,0]);
    $insUnit->execute([$propId1,'Unit B-101','apartment',2,1,900, 13000,0]);
    $insUnit->execute([$propId1,'Studio-01', 'studio',  1,1,450,  8000,0]); $unitAvail = $pdo->lastInsertId();
    $insUnit->execute([$propId2,'Flat 1',    'apartment',2,1,780, 10000,0]);
    $insUnit->execute([$propId2,'Flat 2',    'apartment',2,2,920, 12000,0]);
    $insUnit->execute([$propId2,'Flat 3',    'apartment',3,2,1050,14000,0]);
    $insUnit->execute([$propId3,'Suite 101', 'apartment',2,1,800, 15000,0]);
    $insUnit->execute([$propId3,'Suite 102', 'apartment',3,2,1200,20000,0]);
    step('Seeded: units (9)');

    // Accepted rental request → lease for Alice in Unit A-101
    $pdo->prepare('INSERT INTO rental_requests (unit_id, applicant_name, applicant_email, applicant_phone, occupation, requested_move_in, lease_months, status, decided_at) VALUES (?,?,?,?,?,?,?,?,NOW())')
        ->execute([$unitOcc, 'Alice Akter','alice@tenurex.dev','+880-1700-000004','Software Engineer','2025-01-01',12,'accepted']);
    $rrId = $pdo->lastInsertId();

    // Pending request for Studio-01
    $pdo->prepare('INSERT INTO rental_requests (unit_id, applicant_name, applicant_email, applicant_phone, occupation, requested_move_in, lease_months, status) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$unitAvail,'Mike Siddiqui','mike@tenurex.dev','+880-1700-000005','Teacher','2025-07-01',6,'pending']);
    step('Seeded: rental_requests (2)');

    $pdo->prepare('INSERT INTO leases (unit_id, tenant_user_id, rental_request_id, start_date, end_date, monthly_rent, security_deposit) VALUES (?,?,?,?,?,?,?)')
        ->execute([$unitOcc, $tenantId, $rrId, '2025-01-01','2026-01-01', 12000, 24000]);
    $leaseId = $pdo->lastInsertId();
    $pdo->prepare('UPDATE units SET is_occupied=1 WHERE id=?')->execute([$unitOcc]);
    step('Seeded: leases (1)');

    // Payments for the lease — past 5 months paid, current month upcoming
    $insPayment = $pdo->prepare('INSERT INTO payments (lease_id, amount, due_date, paid_at, method, reference, status) VALUES (?,?,?,?,?,?,?)');
    for ($m = 0; $m < 5; $m++) {
        $due = date('Y-m-01', strtotime("-$m months", strtotime('2026-06-01')));
        $paid = date('Y-m-05', strtotime("-$m months", strtotime('2026-06-01')));
        $insPayment->execute([$leaseId, 12000, $due, $paid, 'bank_transfer', 'TXN-' . strtoupper(bin2hex(random_bytes(4))), 'paid']);
    }
    $insPayment->execute([$leaseId, 12000, '2026-06-01', null, null, null, 'upcoming']);
    step('Seeded: payments (6)');

    // Maintenance requests
    $insMR = $pdo->prepare('INSERT INTO maintenance_requests (property_id, unit_id, reported_by, reporter_name, reporter_email, title, description, category, priority, status) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $insMR->execute([$propId1,$unitOcc,$tenantId,'Alice Akter','alice@tenurex.dev','Leaking kitchen tap','The kitchen tap drips constantly','plumbing','medium','open']);
    $mrId = $pdo->lastInsertId();
    $insMR->execute([$propId1,null,$landlordId,'Arthur Rahman','arthur@tenurex.dev','Lobby light not working','Ceiling light in lobby is out','electrical','low','in_progress']);
    step('Seeded: maintenance_requests (2)');

    // Dispatch Karim to first request
    $pdo->prepare('INSERT INTO technician_requests (maintenance_id, technician_id, scheduled_for, notes, status) VALUES (?,?,?,?,?)')
        ->execute([$mrId, $techId, date('Y-m-d H:i:s', strtotime('+3 days')), 'Bring wrench set', 'dispatched']);
    step('Seeded: technician_requests (1)');

    // Landlord tech approval request
    $pdo->prepare('INSERT INTO landlord_tech_requests (landlord_id, full_name, phone, specialization, nid, years_experience, note) VALUES (?,?,?,?,?,?,?)')
        ->execute([$landlordId,'Rahim Plumber','+880-1700-009999','Plumbing','BD-NID-123456789',7,'Reliable guy from Mirpur']);
    step('Seeded: landlord_tech_requests (1)');

    // Sample message thread
    $pdo->prepare('INSERT INTO message_threads (subject, last_message_at) VALUES (?,NOW())')->execute(['Lease renewal inquiry']);
    $threadId = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO message_thread_participants (thread_id, user_id) VALUES (?,?),(?,?)')->execute([$threadId,$tenantId,$threadId,$landlordId]);
    $pdo->prepare('INSERT INTO messages (thread_id, sender_id, body, sent_at) VALUES (?,?,?,NOW())')->execute([$threadId,$tenantId,'Hi, I would like to inquire about renewing my lease for another 12 months.']);
    $pdo->prepare('INSERT INTO messages (thread_id, sender_id, body, sent_at) VALUES (?,?,?,NOW())')->execute([$threadId,$landlordId,'Hello Alice! That sounds great. I will prepare the renewal documents this week.']);
    $pdo->prepare('UPDATE message_threads SET last_message_at=NOW() WHERE id=?')->execute([$threadId]);
    step('Seeded: messages (1 thread)');

    echo '<hr style="margin:24px 0"><h2 class="ok">✓ Setup complete!</h2>';
    echo '<p>Database <code>tenurex</code> is ready. You can now:</p><ul>
    <li>Visit <a href="http://localhost/TenureX-Web-Project/Pages/login.html">Login page</a></li>
    </ul>';
    echo '<p><strong>⚠️ Delete this file from your server when done:</strong> <code>db/setup.php</code></p>';

} catch (PDOException $e) {
    step('Database error: ' . $e->getMessage(), false);
    echo '<p class="err">Setup failed. Check that XAMPP MySQL is running and the root user has no password.</p>';
}
?>
</body></html>
