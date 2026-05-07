<?php
/**
 * Top header — TenureX logo + search + bell + help.
 * Optionally pass $searchPlaceholder to customize the search box.
 */
$searchPlaceholder = $searchPlaceholder ?? 'Search portfolio...';
?>
<header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center gap-6">
  <div class="flex items-center gap-2">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 21V10l9-7 9 7v11h-7v-7H10v7H3z"/>
    </svg>
    <span class="text-lg font-semibold">TenureX</span>
  </div>

  <form action="search.php" method="get" class="flex-1 max-w-2xl mx-auto relative">
    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
         fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-4.3-4.3"/>
    </svg>
    <input type="text" name="q" placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
           class="w-full pl-9 pr-4 py-2.5 bg-gray-100 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"/>
  </form>

  <div class="flex items-center gap-4">
    <button class="text-gray-500 hover:text-black" aria-label="Notifications">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 00-12 0v3a2 2 0 01-.6 1.6L4 17h5m6 0a3 3 0 11-6 0"/>
      </svg>
    </button>
    <button class="text-gray-500 hover:text-black" aria-label="Help">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.1 9a3 3 0 015.8 1c0 2-3 2-3 4M12 17h.01"/>
      </svg>
    </button>
  </div>
</header>
