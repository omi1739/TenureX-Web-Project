/* =========================================================================
   TenureX — central data store (localStorage-backed)
   Each data page writes its live rows here on load.
   The dashboard reads and renders from here.
   ========================================================================= */

var TENUREX = (function () {

  /* Defaults match what's actually in the HTML pages */
  var DEFAULTS = {
    propertyCount:    5,
    tenantCount:      6,
    requestCount:     4,
    maintenanceCount: 3,

    rentalRequests: [
      { name: 'Sarah Jenkins',  sub: 's.jenkins@example.com', property: 'The Bauhaus Lofts',  unit: 'Unit 402 — Penthouse', status: 'PENDING'   },
      { name: 'David G.',       sub: 'david.g@agency.co',     property: 'Brutalist Heights',   unit: 'Unit 108 — Studio',   status: 'IN REVIEW' },
      { name: 'Elena Moretti',  sub: 'elena@design.it',       property: 'The Bauhaus Lofts',   unit: 'Unit 205 — 2BR',      status: 'PENDING'   },
      { name: 'Marcus T.',      sub: 'm.t@techsol.com',       property: 'Modernist Square',    unit: 'Unit 301 — Loft',     status: 'IN REVIEW' }
    ],

    maintenanceLogs: [
      { issue: 'Leaky Faucet in master bathroom',       reported: 'Oct 24, 2023', tenant: 'Marcus Thorne', unit: 'Elysium Heights, Unit 4B',   status: 'New'      },
      { issue: 'Kitchen island outlet non-functional',  reported: 'Oct 22, 2023', tenant: 'Sienna West',   unit: 'The Obsidian Lofts, 12-A',   status: 'Assigned' },
      { issue: 'Dishwasher making abnormal grinding',   reported: 'Today',        tenant: 'Elara Vance',   unit: 'Heritage Square, Unit 104',  status: 'New'      }
    ]
  };

  /* ── scalar helpers ── */
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

  /* ── list helpers ── */
  function getList(key) {
    try {
      var raw = localStorage.getItem('txl_' + key);
      return raw ? JSON.parse(raw) : JSON.parse(JSON.stringify(DEFAULTS[key] || []));
    } catch (e) {
      return JSON.parse(JSON.stringify(DEFAULTS[key] || []));
    }
  }
  function setList(key, arr) {
    localStorage.setItem('txl_' + key, JSON.stringify(arr));
  }

  return { get: get, set: set, decrement: decrement, getList: getList, setList: setList };
})();
