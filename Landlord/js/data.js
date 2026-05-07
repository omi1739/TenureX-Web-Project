/* =========================================================================
   TenureX — central data store
   Backed by localStorage so counts survive page navigation.
   Each page writes its own count; the dashboard reads all of them.
   ========================================================================= */

var TENUREX = (function () {

  /* Default values that match what's actually in the HTML pages */
  var DEFAULTS = {
    propertyCount:    5,   /* my-properties.html         */
    tenantCount:      6,   /* active-tenants.html        */
    requestCount:     3,   /* rental-requests.html       */
    maintenanceCount: 3    /* maintenance.html (open)    */
  };

  function get(key) {
    var raw = localStorage.getItem('tx_' + key);
    return raw !== null ? parseInt(raw, 10) : DEFAULTS[key];
  }

  function set(key, value) {
    localStorage.setItem('tx_' + key, String(value));
  }

  function decrement(key) {
    set(key, Math.max(0, get(key) - 1));
  }

  return { get: get, set: set, decrement: decrement };
})();
