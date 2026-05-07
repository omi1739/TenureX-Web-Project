<?php
/**
 * Reference implementation: dashboard.html converted to PHP with partials.
 *
 * To convert any other .html page to PHP:
 *   1) Rename foo.html -> foo.php
 *   2) Replace <head>...<body class="..."> with the head.php include
 *   3) Replace the entire <aside> with the sidebar.php include
 *   4) Replace the top <header> with the header.php include
 *   5) Replace the </body></html> with the foot.php include
 *   6) Move hardcoded data into PHP variables and echo them with htmlspecialchars()
 */
declare(strict_types=1);

session_start();

// Once the backend is wired up, fetch real numbers here:
//   require __DIR__ . '/../backend/db.php';
//   $stats = db()->query('SELECT ...')->fetch();
$stats = [
    'total_properties'    => 12,
    'monthly_revenue'     => 48200,
    'pending_applications'=> 24,
    'open_maintenance'    => 7,
];

$pageTitle  = 'Dashboard — TenureX';
$activePage = 'dashboard';

include __DIR__ . '/../partials/head.php';
?>

<div class="flex min-h-screen">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>

  <main class="flex-1 flex flex-col">
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <div class="p-10 flex flex-col gap-8">

      <!-- ============ HEADING + ACTIONS ============ -->
      <section class="flex items-end justify-between flex-wrap gap-6">
        <div>
          <p class="text-xs tracking-widest text-gray-500 mb-2">EXECUTIVE OVERVIEW</p>
          <h2 class="text-5xl font-bold tracking-tight">Portfolio Performance.</h2>
        </div>
        <div class="flex gap-3">
          <a href="add-property.html"
             class="flex items-center gap-2 px-5 py-3 bg-black text-white rounded-lg text-sm font-medium hover:bg-gray-800">
            Add Property
          </a>
          <a href="rental-requests.html"
             class="flex items-center gap-2 px-5 py-3 bg-gray-200 text-gray-800 rounded-lg text-sm font-medium hover:bg-gray-300">
            View Requests
          </a>
        </div>
      </section>

      <!-- ============ STAT CARDS (driven by $stats) ============ -->
      <section class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <p class="text-[11px] tracking-widest text-gray-500">TOTAL PROPERTIES</p>
          <p class="text-3xl font-bold mt-2"><?= (int) $stats['total_properties'] ?></p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <p class="text-[11px] tracking-widest text-gray-500">MONTHLY REVENUE</p>
          <p class="text-3xl font-bold mt-2">$<?= number_format($stats['monthly_revenue']) ?></p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <p class="text-[11px] tracking-widest text-gray-500">PENDING APPLICATIONS</p>
          <p class="text-3xl font-bold mt-2"><?= (int) $stats['pending_applications'] ?></p>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
          <p class="text-[11px] tracking-widest text-gray-500">OPEN MAINTENANCE</p>
          <p class="text-3xl font-bold mt-2"><?= (int) $stats['open_maintenance'] ?></p>
        </div>
      </section>

      <p class="text-sm text-gray-500">
        This is a stub of dashboard.html. The static version still has the full chart + tables —
        copy those sections in here once the backend is wired up.
      </p>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../partials/foot.php'; ?>
