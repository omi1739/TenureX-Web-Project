<?php
/**
 * Sidebar nav — used on every page that has the standard layout.
 *
 * Set $activePage BEFORE including, e.g.
 *   $activePage = 'dashboard';
 *
 * Valid keys match the file basenames:
 *   dashboard, messages, my-properties, rental-requests,
 *   active-tenants, rent-tracking, earnings, maintenance, settings
 */
$activePage = $activePage ?? '';

$nav = [
  ['key' => 'dashboard',       'label' => 'Dashboard',        'href' => 'dashboard.html'],
  ['key' => 'messages',        'label' => 'Messages',         'href' => 'messages.html'],
  ['key' => 'my-properties',   'label' => 'My Properties',    'href' => 'my-properties.html'],
  ['key' => 'rental-requests', 'label' => 'Rental Requests',  'href' => 'rental-requests.html'],
  ['key' => 'active-tenants',  'label' => 'Active Tenants',   'href' => 'active-tenants.html'],
  ['key' => 'rent-tracking',   'label' => 'Rent Tracking',    'href' => 'rent-tracking.html'],
  ['key' => 'earnings',        'label' => 'Earnings',         'href' => 'earnings.html'],
  ['key' => 'maintenance',     'label' => 'Maintenance',      'href' => 'maintenance.html'],
  ['key' => 'settings',        'label' => 'Settings',         'href' => 'settings.html'],
];
?>
<aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between p-6">
  <div>
    <div class="mb-10">
      <h1 class="text-xl font-bold">Portfolio</h1>
      <p class="text-[11px] tracking-widest text-gray-500 mt-1">PREMIUM MANAGEMENT</p>
    </div>

    <nav class="flex flex-col gap-1">
      <?php foreach ($nav as $item):
        $isActive = ($activePage === $item['key']);
        $classes  = $isActive
          ? 'bg-black text-white'
          : 'text-gray-600 hover:bg-gray-100';
      ?>
        <a href="<?= $item['href'] ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $classes ?>">
          <span class="text-sm <?= $isActive ? 'font-medium' : '' ?>"><?= $item['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
    <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white">
      <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-8 2-8 6v2h16v-2c0-4-4-6-8-6z"/>
      </svg>
    </div>
    <div>
      <p class="text-sm font-semibold"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Arthur Sterling') ?></p>
      <p class="text-xs text-gray-500">Portfolio Manager</p>
    </div>
  </div>
</aside>
