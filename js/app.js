/* app.js - extracted from templates/header.php
 * Depends on window.FSA_SCHEDULER injected by PHP in header.php
 */
(function () {
  'use strict';

  const CFG = window.FSA_SCHEDULER || {};
  const PREF_STORAGE_KEY = CFG.PREF_STORAGE_KEY || 'fsa_scheduler_prefs';
  const DEFAULT_PREFERENCES = CFG.DEFAULT_PREFERENCES || {};
  const RESET_ALL_CONFIRM_MSG = CFG.RESET_ALL_CONFIRM_MSG || 'Are you sure?';
  const SAVED_DEFAULT_MSG = CFG.SAVED_DEFAULT_MSG || 'Saved';
  const COPIED_MSG = CFG.COPIED_MSG || 'Copied to clipboard!';

  function showModalNote(message, acceptText) {
    const tNote = (document.documentElement.lang === 'es') ? 'NOTA' : 'NOTE';
    const okText = acceptText || 'OK';

    // Backdrop
    const backdrop = document.createElement('div');
    backdrop.id = 'fsaModalBackdrop';
    backdrop.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;';

    // Modal
    const box = document.createElement('div');
    box.style.cssText = 'background:#fff; width:100%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.35); overflow:hidden;';

    box.innerHTML =
      "<div style='padding:16px 18px; background:#fff3cd; border-bottom:1px solid rgba(0,0,0,0.08);'>" +
        "<div style='font-weight:700; color:#856404; font-size:16px;'>⚠️ " + escapeHtml(tNote) + "</div>" +
      "</div>" +
      "<div style='padding:18px; color:#333; font-size:14px; line-height:1.5;'>" +
        escapeHtml(String(message || '')).replace(/\\n/g, '<br>') +
      "</div>" +
      "<div style='display:flex; gap:12px; justify-content:flex-end; padding:0 18px 18px 18px;'>" +
        "<button type='button' id='fsaModalOkBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#48bb78; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>" +
          escapeHtml(okText) +
        "</button>" +
      "</div>";

    backdrop.appendChild(box);
    document.body.appendChild(backdrop);

    function close() {
      if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
      document.removeEventListener('keydown', onEsc);
    }

    function onEsc(e) {
      if (e && e.key === 'Escape') close();
    }
    document.addEventListener('keydown', onEsc);

    const okBtn = document.getElementById('fsaModalOkBtn');
    if (okBtn) okBtn.addEventListener('click', close);
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function getStoredPreferences() {
    const defaults = { ...DEFAULT_PREFERENCES };
    if (typeof localStorage === 'undefined') {
      return defaults;
    }
    try {
      const raw = localStorage.getItem(PREF_STORAGE_KEY);
      if (raw) {
        const parsed = JSON.parse(raw);

        return { ...defaults, ...parsed };
      }
    } catch (e) {
      // silently ignore
    }
    return defaults;
  }

  function savePreferencesObject(prefs) {
    if (typeof localStorage === 'undefined') {
      return;
    }
    try {
      localStorage.setItem(PREF_STORAGE_KEY, JSON.stringify(prefs));
    } catch (e) {
      // silently ignore
    }
  }

  function persistFormPreferences() {
    const prefs = getStoredPreferences();
    const fields = [
      'local_departure_time',
      'latest_departure_time',
      'minutes_before_departure',
      'hours_after_departure',
      'buffer_time_knots',
      'buffer_time_mach',
      'turnaround_time_input',
      'short_haul',
      'medium_haul',
      'long_haul',
      'ultra_long_haul',

      'cruise_range_corr_enabled',
      'cruise_range_thr1_nm',
      'cruise_range_thr2_nm',
      'cruise_range_thr3_nm',
      'cruise_range_pp_lt_thr1',
      'cruise_range_pp_thr1_thr2',
      'cruise_range_pp_thr2_thr3',
      'cruise_range_pp_ge_thr3',
    ];

    const currentMode = document.getElementById('flight_mode')?.value || 'charter';

    fields.forEach(id => {
      const element = document.getElementById(id);
      if (element) {
        const currentValue = element.value;

        // Store hours_after_departure based on current mode
        if (id === 'hours_after_departure') {
          if (currentMode === 'daily_schedule') {
            prefs.hours_after_departure_daily_schedule = currentValue;
          } else {
            prefs.hours_after_departure_charter = currentValue;
          }
        } else if (id === 'local_departure_time') {
          if (currentMode === 'daily_schedule') {
            prefs.local_departure_time_daily_schedule = currentValue;
          } else {
            prefs.local_departure_time_charter = currentValue;
          }
        } else if (id === 'latest_departure_time') {
          if (currentMode === 'daily_schedule') {
            prefs.latest_departure_time_daily_schedule = currentValue;
          } else {
            prefs.latest_departure_time_charter = currentValue;
          }
        } else if (id === 'minutes_before_departure') {
          prefs.minutes_before_departure = currentValue;
        } else if (id === 'turnaround_time_input') {
          prefs.turnaround_time = currentValue;
        } else {
          prefs[id] = currentValue;
        }
      }
    });

    const aircraftSelect = document.getElementById('aircraft');
    if (aircraftSelect) {
      prefs.aircraft = aircraftSelect.value;
    }

    savePreferencesObject(prefs);
  }

  function applyStoredPreferences() {
    // On reset_all, we want hard defaults, not any stored prefs.
    if (CFG.IS_RESET_ALL) {
      return;
    }

    const prefs = getStoredPreferences();

    // On "Next leg" OR "new day" flows, do NOT overwrite the server-provided local_departure_time
    const skipLocalDeparture = !!CFG.IS_NEXT_LEG || !!CFG.IS_NEW_DAY;

    const map = {
      local_departure_time: skipLocalDeparture ? null : (
        (document.getElementById('flight_mode')?.value === 'daily_schedule')
          ? (prefs.local_departure_time_daily_schedule ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00')
          : (prefs.local_departure_time_charter ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00')
      ),
      latest_departure_time: (
        (document.getElementById('flight_mode')?.value === 'daily_schedule')
          ? (prefs.latest_departure_time_daily_schedule ?? prefs.latest_departure_time ?? DEFAULT_PREFERENCES.latest_departure_time ?? '23:00')
          : (prefs.latest_departure_time_charter ?? prefs.latest_departure_time ?? DEFAULT_PREFERENCES.latest_departure_time ?? '23:00')
      ),
      minutes_before_departure: prefs.minutes_before_departure,
      hours_after_departure: (document.getElementById('flight_mode')?.value === 'daily_schedule')
        ? (prefs.hours_after_departure_daily_schedule !== undefined ? prefs.hours_after_departure_daily_schedule : '1')
        : (prefs.hours_after_departure_charter !== undefined ? prefs.hours_after_departure_charter : '16'),
      buffer_time_knots: prefs.buffer_time_knots,
      buffer_time_mach: prefs.buffer_time_mach,
      turnaround_time_input: (prefs.turnaround_time ?? DEFAULT_PREFERENCES.turnaround_time ?? '60'),
      short_haul: prefs.short_haul,
      medium_haul: prefs.medium_haul,
      long_haul: prefs.long_haul,
      ultra_long_haul: prefs.ultra_long_haul,

      cruise_range_corr_enabled: prefs.cruise_range_corr_enabled,
      cruise_range_thr1_nm: prefs.cruise_range_thr1_nm,
      cruise_range_thr2_nm: prefs.cruise_range_thr2_nm,
      cruise_range_thr3_nm: prefs.cruise_range_thr3_nm,
      cruise_range_pp_lt_thr1: prefs.cruise_range_pp_lt_thr1,
      cruise_range_pp_thr1_thr2: prefs.cruise_range_pp_thr1_thr2,
      cruise_range_pp_thr2_thr3: prefs.cruise_range_pp_thr2_thr3,
      cruise_range_pp_ge_thr3: prefs.cruise_range_pp_ge_thr3
    };

    Object.entries(map).forEach(([id, value]) => {
      const el = document.getElementById(id);
      if (el) {
        if (value !== '' && value !== null && value !== undefined) {
          el.value = value;
        }
      }
    });

    // --- Ensure aircraft preference is applied reliably ---
    const aircraftSelect = document.getElementById('aircraft');
    if (aircraftSelect) {
      const prefAircraft = prefs.aircraft;
      if (prefAircraft !== '' && prefAircraft !== null && prefAircraft !== undefined) {
        aircraftSelect.value = prefAircraft;
      }
    }

    // Keep hidden "saved default" in sync so PHP can use it as baseline (even if user saved earlier)
    try {
      const savedField = document.getElementById('local_departure_time_saved');
      const modeNow = document.getElementById('flight_mode')?.value || 'charter';
      const defaultDep = (modeNow === 'daily_schedule')
        ? (prefs.local_departure_time_daily_schedule ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00')
        : (prefs.local_departure_time_charter ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00');

      if (savedField && defaultDep !== '' && defaultDep !== null && defaultDep !== undefined) {
        savedField.value = String(defaultDep);
      }
    } catch (e) {}
  }

function validateIcaoFields() {
  const icaoDep = document.getElementById('icao_dep');
  const icaoArr = document.getElementById('icao_arr');

  if (!icaoDep || !icaoArr) return true;

  if (!icaoDep.value.trim() || !icaoArr.value.trim()) {
    const htmlLang = document.documentElement.lang || 'en';
    const warningText = (htmlLang === 'es')
      ? '¡Es obligatorio indicar LOS CÓDIGOS OACI DE SALIDA Y DE LLEGADA!'
      : 'DEPARTURE AND ARRIVAL ICAO codes are required!';

    showModalNote(warningText, 'OK');
    return false;
  }

  return true;
}

  function setupPreferenceWatchers() {
    [
      'local_departure_time',
      'latest_departure_time',
      'minutes_before_departure',
      'hours_after_departure',
      'buffer_time_knots',
      'buffer_time_mach',
      'turnaround_time_input',
      'flight_mode',
      'aircraft',
      'short_haul',
      'medium_haul',
      'long_haul',
      'ultra_long_haul',

      'cruise_range_corr_enabled',
      'cruise_range_thr1_nm',
      'cruise_range_thr2_nm',
      'cruise_range_thr3_nm',
      'cruise_range_pp_lt_thr1',
      'cruise_range_pp_thr1_thr2',
      'cruise_range_pp_thr2_thr3',
      'cruise_range_pp_ge_thr3'
    ].forEach(id => {

      const element = document.getElementById(id);
      if (element) {
        element.addEventListener('change', persistFormPreferences);
      }
    });
  }

   function showSavedNotification() {
    let notification = document.getElementById('savedNotification');
    if (!notification) {
      notification = document.createElement('div');
      notification.id = 'savedNotification';
      notification.innerHTML = '✓ ' + SAVED_DEFAULT_MSG;
      notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: #48bb78;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
            pointer-events: none;
        `;
      document.body.appendChild(notification);
    }
    requestAnimationFrame(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateX(-50%) translateY(0)';
    });
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (document.body.contains(notification)) {
          document.body.removeChild(notification);
        }
      }, 300);
    }, 2500);
  }

  function saveDepartureDefault() {
    const timeInput = document.getElementById('local_departure_time');
    if (!timeInput) {
      alert('Local departure time input not found');
      return;
    }
    const time = timeInput.value;
    if (!time) {
      alert('Please select a time first');
      return;
    }

    const prefs = getStoredPreferences();
    const currentMode = document.getElementById('flight_mode')?.value || 'charter';
    if (currentMode === 'daily_schedule') {
      prefs.local_departure_time_daily_schedule = time;
    } else {
      prefs.local_departure_time_charter = time;
    }
    savePreferencesObject(prefs);

    const savedField = document.getElementById('local_departure_time_saved');
    if (savedField) {
      savedField.value = time;
    }

    timeInput.value = time;
    showSavedNotification();
  }

  function saveArrivalDefault() {
    const time = document.getElementById('latest_departure_time')?.value;
    if (!time) {
      alert('Please select a time first');
      return;
    }

    const prefs = getStoredPreferences();
    const currentMode = document.getElementById('flight_mode')?.value || 'charter';

    if (currentMode === 'daily_schedule') {
      prefs.latest_departure_time_daily_schedule = time;
    } else {
      prefs.latest_departure_time_charter = time;
    }

    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveAircraftDefault() {
    const aircraftSelect = document.getElementById('aircraft');
    if (!aircraftSelect) {
      alert('Aircraft selector not found');
      return;
    }
    const aircraft = aircraftSelect.value;
    if (!aircraft) {
      alert('Please select an aircraft first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.aircraft = aircraft;
    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveTurnaroundDefault() {
    const input = document.getElementById('turnaround_time_input');
    if (!input) {
      alert('turnaround_time_input input not found');
      return;
    }
    const time = input.value;
    if (!time) {
      alert('Please enter a turnaround time first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.turnaround_time = time;
    savePreferencesObject(prefs);

    showSavedNotification();
  }

  function saveMinutesBeforeDefault() {
    const input = document.getElementById('minutes_before_departure');
    if (!input) {
      alert('Minutes before input not found');
      return;
    }
    const value = input.value;
    if (!value) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.minutes_before_departure = value;
    savePreferencesObject(prefs);

    showSavedNotification();
  }

  function saveHoursAfterDefault() {
    const input = document.getElementById('hours_after_departure');
    if (!input) {
      alert('Hours after input not found');
      return;
    }
    const value = input.value;
    if (!value) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    const currentMode = document.getElementById('flight_mode')?.value || 'charter';

    if (currentMode === 'daily_schedule') {
      prefs.hours_after_departure_daily_schedule = value;
    } else {
      prefs.hours_after_departure_charter = value;
    }

    savePreferencesObject(prefs);

    showSavedNotification();
  }

   function saveBufferTimeKnotsDefault() {
    const input = document.getElementById('buffer_time_knots');
    if (!input) {
      alert('Buffer time knots input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.buffer_time_knots = value;
    savePreferencesObject(prefs);

    showSavedNotification();
  }

  function saveBufferTimeMachDefault() {
    const input = document.getElementById('buffer_time_mach');
    if (!input) {
      alert('Buffer time Mach input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.buffer_time_mach = value;
    savePreferencesObject(prefs);

    showSavedNotification();
  }

  // --- Haul percentage defaults ---
  function saveShortHaulDefault() {
    const input = document.getElementById('short_haul');
    if (!input) {
      alert('Short haul input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.short_haul = value;
    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveMediumHaulDefault() {
    const input = document.getElementById('medium_haul');
    if (!input) {
      alert('Medium haul input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.medium_haul = value;
    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveLongHaulDefault() {
    const input = document.getElementById('long_haul');
    if (!input) {
      alert('Long haul input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.long_haul = value;
    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveUltraLongHaulDefault() {
    const input = document.getElementById('ultra_long_haul');
    if (!input) {
      alert('Ultra-long haul input not found');
      return;
    }
    const value = input.value;
    if (value === '' || value === null || value === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs.ultra_long_haul = value;
    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveCruiseRangeCorrectionDefault() {
    const ids = [
      'cruise_range_corr_enabled',
      'cruise_range_thr1_nm',
      'cruise_range_thr2_nm',
      'cruise_range_thr3_nm',
      'cruise_range_pp_lt_thr1',
      'cruise_range_pp_thr1_thr2',
      'cruise_range_pp_thr2_thr3',
      'cruise_range_pp_ge_thr3'
    ];

    const prefs = getStoredPreferences();

    for (const id of ids) {
      const el = document.getElementById(id);
      if (!el) {
        alert(id + ' input not found');
        return;
      }
      const v = el.value;
      if (v === '' || v === null || v === undefined) {
        alert('Please enter a value first');
        return;
      }
      prefs[id] = v;
    }

    savePreferencesObject(prefs);
    showSavedNotification();
  }

  function saveCruiseRangeFieldDefault(fieldId) {
    const id = String(fieldId || '').trim();
    if (!id) return;

    const el = document.getElementById(id);
    if (!el) {
      alert(id + ' input not found');
      return;
    }

    const v = el.value;
    if (v === '' || v === null || v === undefined) {
      alert('Please enter a value first');
      return;
    }

    const prefs = getStoredPreferences();
    prefs[id] = v;
    savePreferencesObject(prefs);

    // Always show the same green toast used by other "Save default" buttons
    try { showSavedNotification(); } catch (e) {}
  }

  function toggleLatestArrivalTime(doReset) {
    const flightModeEl = document.getElementById('flight_mode');
    if (!flightModeEl) return;

    const flightMode = flightModeEl.value || 'charter';

    if (doReset) {
      // Clear ICAO fields when switching modes
      try {
        const dep = document.getElementById('icao_dep');
        const arr = document.getElementById('icao_arr');
        if (dep) dep.value = '';
        if (arr) arr.value = '';
      } catch (e) {
        // ignore
      }
    }

    // Reset earliest/local and latest times when switching modes (always, even on Next Leg pages)
    {
      try {
        const prefs = getStoredPreferences();
        const depInput = document.getElementById('local_departure_time');

        // On initial load for "Next leg" / "new day", keep server time.
        // But if user triggers a reset (mode change), allow resetting.
        if (depInput && (doReset || (!CFG.IS_NEXT_LEG && !CFG.IS_NEW_DAY))) {
          if (flightMode === 'daily_schedule') {
            depInput.value = (prefs.local_departure_time_daily_schedule ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00');
          } else {
            depInput.value = (prefs.local_departure_time_charter ?? prefs.local_departure_time ?? DEFAULT_PREFERENCES.local_departure_time ?? '07:00');
          }
        }

        const latestInput = document.getElementById('latest_departure_time');
        if (latestInput) {
          if (flightMode === 'daily_schedule') {
            latestInput.value = (prefs.latest_departure_time_daily_schedule ?? prefs.latest_departure_time ?? DEFAULT_PREFERENCES.latest_departure_time ?? '23:00');
          } else {
            latestInput.value = (prefs.latest_departure_time_charter ?? prefs.latest_departure_time ?? DEFAULT_PREFERENCES.latest_departure_time ?? '23:00');
          }
        }
      } catch (e) {
        // ignore
      }
    }

    const latestArrivalInline = document.getElementById('latestArrivalInline');
    const hoursAfterInput = document.getElementById('hours_after_departure');

    if (hoursAfterInput) {
      const storedValue = localStorage.getItem(PREF_STORAGE_KEY);
      let prefs = {};
      if (storedValue) {
        try { prefs = JSON.parse(storedValue); } catch (e) { prefs = {}; }
      }

      if (flightMode === 'daily_schedule') {
        hoursAfterInput.value = prefs.hours_after_departure_daily_schedule !== undefined ? prefs.hours_after_departure_daily_schedule : '1';
      } else {
        hoursAfterInput.value = prefs.hours_after_departure_charter !== undefined ? prefs.hours_after_departure_charter : '16';
      }
    }

    if (flightMode === 'daily_schedule') {
      if (latestArrivalInline) latestArrivalInline.style.display = 'block';
    } else {
      if (latestArrivalInline) latestArrivalInline.style.display = 'none';
    }
  }

  function resetAllValues() {
    if (!confirm(RESET_ALL_CONFIRM_MSG)) {
      return;
    }

    // Clear all localStorage keys used by this app (current + legacy)
    try {
      if (typeof localStorage !== 'undefined' && localStorage) {
        // Remove current key
        try { localStorage.removeItem(PREF_STORAGE_KEY); } catch (e) {}

        // Remove any legacy/alternate keys
        for (let i = localStorage.length - 1; i >= 0; i--) {
          const k = localStorage.key(i);
          if (!k) continue;

          // Conservative: only remove our app keys
          if (
            k === 'fsa_scheduler_prefs' ||
            k.startsWith('fsa_scheduler_') ||
            k.startsWith('fsa_')
          ) {
            try { localStorage.removeItem(k); } catch (e) {}
          }
        }
      }
    } catch (e) {
      // ignore
    }

    // Clear sessionStorage too (in case anything was stored there)
    try {
      if (typeof sessionStorage !== 'undefined' && sessionStorage) {
        for (let i = sessionStorage.length - 1; i >= 0; i--) {
          const k = sessionStorage.key(i);
          if (!k) continue;
          if (
            k === 'fsa_scheduler_prefs' ||
            k.startsWith('fsa_scheduler_') ||
            k.startsWith('fsa_')
          ) {
            try { sessionStorage.removeItem(k); } catch (e) {}
          }
        }
      }
    } catch (e) {
      // ignore
    }

    // Clear all fsa_* cookies
    try {
      document.cookie.split(';').forEach(function (cookie) {
        const name = cookie.trim().split('=')[0];
        if (name.startsWith('fsa_')) {
          document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
        }
      });
      document.cookie = 'fsa_scheduler_lang=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
    } catch (e) {
      // ignore
    }

    // Force a fresh GET (cache-bust so no stale state survives)
    // Go through a dedicated reset route so both server + JS can enforce defaults
    window.location.replace('?reset_all=1&_t=' + Date.now());
  }

  function showCopiedNotification() {
    const msg = (window.FSA_SCHEDULER && window.FSA_SCHEDULER.COPIED_MSG) ? window.FSA_SCHEDULER.COPIED_MSG : 'Copied to clipboard!';

    let notification = document.getElementById('copiedNotification');
    if (!notification) {
      notification = document.createElement('div');
      notification.id = 'copiedNotification';
      notification.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.98);
            background: #4299e1;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.3s, transform 0.3s;
            z-index: 1000;
            pointer-events: none;
        `;
      document.body.appendChild(notification);
    }

    notification.innerHTML = '✓ ' + msg;

    requestAnimationFrame(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateX(-50%) translateY(0)';
    });

    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (document.body.contains(notification)) {
          document.body.removeChild(notification);
        }
      }, 300);
    }, 1500);
  }

  function copyToClipboard(text) {
    const textWithoutColon = String(text).replace(':', '');

    const doToast = () => {
      showCopiedNotification();
    };

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      navigator.clipboard.writeText(textWithoutColon).then(doToast).catch(() => {
        try {
          const ta = document.createElement('textarea');
          ta.value = textWithoutColon;
          ta.style.position = 'fixed';
          ta.style.left = '-9999px';
          ta.style.top = '0';
          document.body.appendChild(ta);
          ta.focus();
          ta.select();
          const ok = document.execCommand('copy');
          document.body.removeChild(ta);
          if (ok) doToast();
        } catch (e) {
        }
      });
      return;
    }

    try {
      const ta = document.createElement('textarea');
      ta.value = textWithoutColon;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      ta.style.top = '0';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      const ok = document.execCommand('copy');
      document.body.removeChild(ta);
      if (ok) doToast();
    } catch (e) {
    }
  }

  // Expose functions used by inline onclick attributes in templates
  window.persistFormPreferences = persistFormPreferences;
  window.applyStoredPreferences = applyStoredPreferences;
  window.validateIcaoFields = validateIcaoFields;
  window.toggleLatestArrivalTime = toggleLatestArrivalTime;

  window.saveDepartureDefault = saveDepartureDefault;
  window.saveDeparturDefault = saveDepartureDefault;
  window.saveArrivalDefault = saveArrivalDefault;
  window.saveTurnaroundDefault = saveTurnaroundDefault;
  window.saveAircraftDefault = saveAircraftDefault;
 
  window.saveMinutesBeforeDefault = saveMinutesBeforeDefault;
  window.saveHoursAfterDefault = saveHoursAfterDefault;
  window.saveBufferTimeKnotsDefault = saveBufferTimeKnotsDefault;
  window.saveBufferTimeMachDefault = saveBufferTimeMachDefault;

  window.saveShortHaulDefault = saveShortHaulDefault;
  window.saveMediumHaulDefault = saveMediumHaulDefault;
  window.saveLongHaulDefault = saveLongHaulDefault;
  window.saveUltraLongHaulDefault = saveUltraLongHaulDefault;
  window.saveCruiseRangeCorrectionDefault = saveCruiseRangeCorrectionDefault;
  window.saveCruiseRangeFieldDefault = saveCruiseRangeFieldDefault;

  window.resetAllValues = resetAllValues;

  window.copyToClipboard = function (text, el) {
    copyToClipboard(text);
  };

  document.addEventListener('DOMContentLoaded', function () {

    // If server sanitized cruise-range correction inputs, overwrite localStorage with safe values and warn.
    try {
      if (CFG.CRUISE_RANGE_CORR_SANITIZED && CFG.CRUISE_RANGE_CORR_SANITIZED_VALUES) {
        const vals = CFG.CRUISE_RANGE_CORR_SANITIZED_VALUES;
        const ids = [
          'cruise_range_corr_enabled',
          'cruise_range_thr1_nm',
          'cruise_range_thr2_nm',
          'cruise_range_thr3_nm',
          'cruise_range_pp_lt_thr1',
          'cruise_range_pp_thr1_thr2',
          'cruise_range_pp_thr2_thr3',
          'cruise_range_pp_ge_thr3'
        ];

        // Overwrite DOM values
        ids.forEach(function (id) {
          const el = document.getElementById(id);
          if (el && vals[id] !== undefined && vals[id] !== null && vals[id] !== '') {
            el.value = String(vals[id]);
          }
        });

        // Overwrite stored prefs so invalid values do not re-apply
        const prefs = getStoredPreferences();
        ids.forEach(function (id) {
          if (vals[id] !== undefined && vals[id] !== null && vals[id] !== '') {
            prefs[id] = String(vals[id]);
          }
        });
        savePreferencesObject(prefs);

        // Warn user
        const msg = CFG.CRUISE_RANGE_CORR_SANITIZED_MSG || 'Cruise range correction settings were invalid and have been reset.';
        showModalNote(msg, 'OK');
      }
    } catch (e) {}

    if (CFG.IS_RESET_ALL) {
      // Force the four haul inputs to hard defaults (server defaults) to defeat browser form-restore.
      try {
        const setVal = (id, v) => {
          const el = document.getElementById(id);
          if (el && v !== undefined && v !== null) el.value = String(v);
        };

        // Pull defaults from injected DEFAULT_PREFERENCES (which comes from config.php)
        setVal('short_haul', DEFAULT_PREFERENCES.short_haul);
        setVal('medium_haul', DEFAULT_PREFERENCES.medium_haul);
        setVal('long_haul', DEFAULT_PREFERENCES.long_haul);
        setVal('ultra_long_haul', DEFAULT_PREFERENCES.ultra_long_haul);

        setVal('cruise_range_corr_enabled', DEFAULT_PREFERENCES.cruise_range_corr_enabled);
        setVal('cruise_range_thr1_nm', DEFAULT_PREFERENCES.cruise_range_thr1_nm);
        setVal('cruise_range_thr2_nm', DEFAULT_PREFERENCES.cruise_range_thr2_nm);
        setVal('cruise_range_thr3_nm', DEFAULT_PREFERENCES.cruise_range_thr3_nm);
        setVal('cruise_range_pp_lt_thr1', DEFAULT_PREFERENCES.cruise_range_pp_lt_thr1);
        setVal('cruise_range_pp_thr1_thr2', DEFAULT_PREFERENCES.cruise_range_pp_thr1_thr2);
        setVal('cruise_range_pp_thr2_thr3', DEFAULT_PREFERENCES.cruise_range_pp_thr2_thr3);
        setVal('cruise_range_pp_ge_thr3', DEFAULT_PREFERENCES.cruise_range_pp_ge_thr3);

        // Remove the reset flag from the URL so reloads/bookmarks are clean
        try {
          const url = new URL(window.location.href);
          url.searchParams.delete('reset_all');
          url.searchParams.delete('_t');
          window.history.replaceState({}, '', url.toString());
        } catch (e) {}
      } catch (e) {}

      // Still apply the rest of initialization (but not stored prefs)
      setupPreferenceWatchers();
      toggleLatestArrivalTime();
    } else {
      applyStoredPreferences();
      setupPreferenceWatchers();
      toggleLatestArrivalTime();
    }

    const turnaroundInput = document.getElementById('turnaround_time_input');
    const nextLegButtons = document.querySelectorAll('.button-next-leg');
    nextLegButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        if (turnaroundInput) {
        }
      });
    });

    // Force ALL text inputs to uppercase (real value, not only visual), excluding URL fields
    document.querySelectorAll('input[type="text"]').forEach(function (input) {
      if (input.id === 'extract_url') return;
      input.addEventListener('input', function () {
        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.value = input.value.toUpperCase();
        if (start !== null && end !== null) {
          input.setSelectionRange(start, end);
        }
      });
    });
  });


})();
