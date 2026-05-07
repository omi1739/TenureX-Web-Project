/* =========================================================================
   Shared JavaScript for the TenureX UI.
   Plain JS (no frameworks). Each block is small and self-contained so any
   page that doesn't use a feature simply has nothing to bind to.
   ========================================================================= */


/* -------------------------------------------------------------------------
   Property card "3-dot" menu (used on my-properties.html)

   HTML pattern each card uses:
     <button data-card-menu-trigger> ⋮ </button>
     <div    data-card-menu          class="hidden ..."> ... </div>

   Behavior:
     1. Clicking the trigger toggles its sibling menu open/closed.
     2. Opening one menu automatically closes any other menu that is open.
     3. Clicking anywhere outside any menu closes all of them.
   ------------------------------------------------------------------------- */

// Find every 3-dot trigger on the page and attach a click handler to it.
document.querySelectorAll('[data-card-menu-trigger]').forEach(function (trigger) {
  trigger.addEventListener('click', function (event) {
    // Prevent the click from bubbling up to the document-level handler
    // below — otherwise the menu would close again immediately.
    event.stopPropagation();

    // The menu sits right after the button in the HTML.
    var menu = trigger.nextElementSibling;

    // Close every OTHER menu first, so only one is open at a time.
    document.querySelectorAll('[data-card-menu]').forEach(function (other) {
      if (other !== menu) {
        other.classList.add('hidden');
      }
    });

    // Toggle this menu's visibility.
    menu.classList.toggle('hidden');
  });
});

// Clicking anywhere on the page (outside a menu) closes all menus.
document.addEventListener('click', function () {
  document.querySelectorAll('[data-card-menu]').forEach(function (menu) {
    menu.classList.add('hidden');
  });
});
