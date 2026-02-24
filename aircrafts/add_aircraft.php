<?php
// add_aircraft.php – fragment that is included inside the main page
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../preferences.php';
require_once __DIR__ . '/../aircrafts/aircraft.php';
require_once __DIR__ . '/../wind/wind_climo.php';
require_once __DIR__ . '/../src/bootstrap.php';

$langResult = handleLanguageSelection();
$lang = $langResult['lang'];

// Collect ALL messages/errors to show at the top (under the search title)
$pre_title_message_html = '';
$top_messages_html = '';

// Success message vars (must exist before HTML)
$aircraft_saved_ok = false;
$aircraft_saved_name = '';

// --------------------------------------------------------------
// Process POST actions BEFORE rendering any HTML (so top messages work)
// --------------------------------------------------------------
$data = [];
$showReviewWarning = false;
$action = $_POST['action'] ?? '';

if ($action === 'extract' && !empty($_POST['url'])) {
	$url  = trim((string)$_POST['url']);

	$parts = parse_url($url);
	$host = strtolower((string)($parts['host'] ?? ''));

	if ($host !== 'learningzone.eurocontrol.int') {
		$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
				<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['only_eurocontrol_urls_allowed'] ?? ($lang['missing_translation'] ?? '')) . "
			  </div>";
		$action = '';
	} else {

	$ctx = stream_context_create([
		'http' => [
			'timeout' => 10,
			'header'  => "User-Agent: FSAirlines-Flight-Scheduler/1.1\r\n",
		],
		'ssl' => [
			'verify_peer'      => true,
			'verify_peer_name' => true,
		],
	]);
	$html = @file_get_contents($url, false, $ctx);

	if ($html === false) {
		$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
				<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['error_eurocontrol_fetch'] ?? ($lang['missing_translation'] ?? '')) . "
			  </div>";
	} else {
		$data = extractData($html);

		$allNull = true;
		$skipKeys = ['type','manufacturer','aircraftName','icaoCode'];
		foreach ($data as $k => $v) {
			if (in_array($k, $skipKeys, true)) continue;
			if ($v !== null) { $allNull = false; break; }
		}

		if ($allNull) {

			$msg = (string)($lang['no_valid_aircraft_data_fetched'] ?? ($lang['missing_translation'] ?? ''));

			$tOk = (string)($lang['accept'] ?? ($lang['missing_translation'] ?? ''));

			echo "<div id='noValidDataModalBackdrop' style='position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;'>
					<div style='background:#fff; width:100%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.35); overflow:hidden;'>
						<div style='padding:16px 18px; background:#fff3cd; border-bottom:1px solid rgba(0,0,0,0.08);'>
							<div style='font-weight:700; color:#856404; font-size:16px;'>⚠️ " . htmlspecialchars($lang['note'] ?? ($lang['missing_translation'] ?? '')) . "</div>
						</div>
						<div style='padding:18px; color:#333; font-size:14px; line-height:1.5;'>
							" . nl2br(htmlspecialchars($msg)) . "
						</div>
						<div style='display:flex; gap:12px; justify-content:flex-end; padding:0 18px 18px 18px;'>
							<button type='button' id='noValidDataOkBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#48bb78; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>
								" . htmlspecialchars($tOk) . "
							</button>
						</div>
					</div>
				  </div>";

			echo "<script>
				(function () {
					var okBtn    = document.getElementById('noValidDataOkBtn');
					var backdrop = document.getElementById('noValidDataModalBackdrop');

					function closeModal() {
						if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
					}

					if (okBtn) {
						okBtn.addEventListener('click', function () {
							closeModal();
							window.location.href = '?add_aircraft=1';
						});
					}

					// Click outside (backdrop) closes
					if (backdrop) {
						backdrop.addEventListener('click', function (e) {
							if (e && e.target === backdrop) {
								closeModal();
								window.location.href = '?add_aircraft=1';
							}
						});
					}

					// ESC closes
					document.addEventListener('keydown', function (e) {
						if (e && e.key === 'Escape') {
							closeModal();
							window.location.href = '?add_aircraft=1';
						}
					});
				})();
			</script>";
			exit;
		} else {
			$showReviewWarning = true;
			// Scroll down after extract (force it)
			echo "<script>
				window.location.hash = 'footer-start';
				setTimeout(function(){ window.location.hash = 'footer-start'; }, 50);
			</script>";

		}
	}
	}
	$action = '';
}

if ($action === 'accept_extract') {

	$newKey = strtoupper(trim((string)($_POST['aircraft_display'] ?? '')));

	if ($newKey === '') {
		$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
				<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_missing_name']) . "
			  </div>";
		$action = '';
	} else {

		$aircraftFile = __DIR__ . '/../aircrafts/aircraft.php';

		if (!file_exists($aircraftFile)) {
			$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
					<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_found']) . "
				  </div>";
			$action = '';
		} elseif (!is_readable($aircraftFile)) {
			$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
					<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_readable']) . "
				  </div>";
			$action = '';
		} elseif (!is_writable($aircraftFile)) {
			$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
					<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_writable']) . "
				  </div>";
			$action = '';
		} else {

			$aircraft_list_current = require $aircraftFile;
			if (!is_array($aircraft_list_current)) {
				$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
						<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_invalid']) . "
					  </div>";
				$action = '';
			} else {

				$exists = array_key_exists($newKey, $aircraft_list_current);
				$overwriteConfirmed = (isset($_POST['overwrite_confirm']) && $_POST['overwrite_confirm'] === '1');

				if ($exists && !$overwriteConfirmed) {

					$msg = (string)($lang['add_aircraft_exists_overwrite'] ?? ($lang['missing_translation'] ?? ''));
					$tAccept = (string)($lang['accept'] ?? ($lang['missing_translation'] ?? ''));
					$tCancel = (string)($lang['cancel'] ?? ($lang['missing_translation'] ?? ''));

					// Create a hidden form that re-posts with overwrite_confirm=1 (same data)
					$form = "<form id='overwriteConfirmForm' method='post' action=''>"
						  . "<input type='hidden' name='action' value='accept_extract'>"
						  . "<input type='hidden' name='overwrite_confirm' value='1'>"
						  . "<input type='hidden' name='aircraft_display' value='" . htmlspecialchars($newKey) . "'>";

					$fields = [
						'type',
						'initialClimbIAS','initialClimbROC',
						'climb150IAS','climb150ROC',
						'climb240IAS','climb240ROC',
						'machClimbMACH','machClimbROC',
						'cruiseTAS','cruiseMACH','cruiseCeiling','cruiseRange',
						'initialDescentMACH','initialDescentROD',
						'descentIAS','descentROD',
						'approachIAS','approachROD'
					];

					foreach ($fields as $f) {
						$val = (string)($_POST[$f] ?? '');
						$form .= "<input type='hidden' name='" . htmlspecialchars($f) . "' value='" . htmlspecialchars($val) . "'>";
					}

					$form .= "</form>";

					// IMPORTANT: This file is included inside the main page.
					// Do NOT exit here; store the modal and let the full page render.
					$top_messages_html .= $form;

					$top_messages_html .= "<div id='overwriteModalBackdrop' style='position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;'>
							<div style='background:#fff; width:100%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.35); overflow:hidden;'>
								<div style='padding:16px 18px; background:#fff3cd; border-bottom:1px solid rgba(0,0,0,0.08);'>
									<div style='font-weight:700; color:#856404; font-size:16px;'>⚠️ " . htmlspecialchars($lang['note'] ?? ($lang['missing_translation'] ?? '')) . "</div>
								</div>
								<div style='padding:18px; color:#333; font-size:14px; line-height:1.5;'>
									" . nl2br(htmlspecialchars($msg)) . "
								</div>
								<div style='display:flex; gap:12px; justify-content:flex-end; padding:0 18px 18px 18px;'>
									<button type='button' id='overwriteCancelBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#e53e3e; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>
										" . htmlspecialchars($tCancel) . "
									</button>
									<button type='button' id='overwriteAcceptBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#48bb78; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>
										" . htmlspecialchars($tAccept) . "
									</button>
								</div>
							</div>
						  </div>";

					$top_messages_html .= "<script>
						(function () {
							var acceptBtn = document.getElementById('overwriteAcceptBtn');
							var cancelBtn = document.getElementById('overwriteCancelBtn');
							var backdrop  = document.getElementById('overwriteModalBackdrop');
							var form      = document.getElementById('overwriteConfirmForm');

							function closeModal() {
								if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
							}

							if (acceptBtn) {
								acceptBtn.addEventListener('click', function () {
									if (form) form.submit();
								});
							}

							if (cancelBtn) {
								cancelBtn.addEventListener('click', function () {
									closeModal();
									window.location.href = '?add_aircraft=1';
								});
							}

							document.addEventListener('keydown', function (e) {
								if (e && e.key === 'Escape') {
									closeModal();
									window.location.href = '?add_aircraft=1';
								}
							});
						})();
					</script>";

					// Stop processing this POST, but keep rendering the page
					$action = '';

				} else {

					$numOrNull = function($v) {
						if ($v === null) return null;
						$v = trim((string)$v);
						if ($v === '') return null;
						return is_numeric($v) ? ($v + 0) : null;
					};

					$newProfile = [
						'type'               => (string)($_POST['type'] ?? ''),
						'initialClimbIAS'    => $numOrNull($_POST['initialClimbIAS'] ?? null),
						'initialClimbROC'    => $numOrNull($_POST['initialClimbROC'] ?? null),
						'climb150IAS'        => $numOrNull($_POST['climb150IAS'] ?? null),
						'climb150ROC'        => $numOrNull($_POST['climb150ROC'] ?? null),
						'climb240IAS'        => $numOrNull($_POST['climb240IAS'] ?? null),
						'climb240ROC'        => $numOrNull($_POST['climb240ROC'] ?? null),
						'machClimbMACH'      => $numOrNull($_POST['machClimbMACH'] ?? null),
						'machClimbROC'       => $numOrNull($_POST['machClimbROC'] ?? null),
						'cruiseTAS'          => $numOrNull($_POST['cruiseTAS'] ?? null),
						'cruiseMACH'         => $numOrNull($_POST['cruiseMACH'] ?? null),
						'cruiseCeiling'      => $numOrNull($_POST['cruiseCeiling'] ?? null),
						'cruiseRange'        => $numOrNull($_POST['cruiseRange'] ?? null),
						'initialDescentMACH' => $numOrNull($_POST['initialDescentMACH'] ?? null),
						'initialDescentROD'  => $numOrNull($_POST['initialDescentROD'] ?? null),
						'descentIAS'         => $numOrNull($_POST['descentIAS'] ?? null),
						'descentROD'         => $numOrNull($_POST['descentROD'] ?? null),
						'approachIAS'        => $numOrNull($_POST['approachIAS'] ?? null),
						'approachROD'        => $numOrNull($_POST['approachROD'] ?? null),
					];

					// Normalize type to allowed values
					$t = strtolower(trim((string)($newProfile['type'] ?? '')));
					if ($t !== 'mach' && $t !== 'knots') {
						$t = 'mach';
					}
					$newProfile['type'] = $t;

					$aircraft_list_current[$newKey] = $newProfile;
					uksort($aircraft_list_current, function ($a, $b) {
						$na = (string)$a;
						$nb = (string)$b;

						// Treat Boeing-style suffixes as series: -8 => -800, -9 => -900, -10 => -1000
						$na = preg_replace('/-(10)(?!\d)/', '-1000', $na);
						$nb = preg_replace('/-(10)(?!\d)/', '-1000', $nb);
						$na = preg_replace('/-(8)(?!\d)/', '-800', $na);
						$nb = preg_replace('/-(8)(?!\d)/', '-800', $nb);
						$na = preg_replace('/-(9)(?!\d)/', '-900', $na);
						$nb = preg_replace('/-(9)(?!\d)/', '-900', $nb);

						return strnatcasecmp($na, $nb);
					});

					// Write atomically to avoid corrupting aircraft.php on partial writes
					$out = "<?php\n\n";
					$out .= "\$aircraft_list = " . var_export($aircraft_list_current, true) . ";\n\n";
					$out .= "return \$aircraft_list;\n";

					$tmpFile = $aircraftFile . '.tmp';
					$bytes = @file_put_contents($tmpFile, $out, LOCK_EX);
					if ($bytes !== false) {
						@rename($tmpFile, $aircraftFile);
					}
					if ($bytes === false || !file_exists($aircraftFile)) {
						$pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
								<strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_write_failed']) . "
							  </div>";
					} else {
						$redir = '?add_aircraft=1&aircraft_added=1&aircraft_name=' . rawurlencode($newKey);

						echo "<script>
						window.location.replace(" . json_encode($redir . '#top') . ");
						</script>";

						return;
					}

					$action = '';
				}
			}
		}
	}
}

/* --------------------------------------------------------------
   Helper – fetch text content of an element by its id
   -------------------------------------------------------------- */
function getNodeText(DOMDocument $doc, string $id) : ?string {
    $el = $doc->getElementById($id);
    return $el ? trim($el->textContent) : null;
}

/* --------------------------------------------------------------
   Core – extract the performance data from the raw HTML
   -------------------------------------------------------------- */
function extractData(string $html) : array {
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    $raw = [
        'manufacturer'       => getNodeText($doc, 'MainContent_wsManufacturerLabel'),
        'aircraftName'       => getNodeText($doc, 'MainContent_wsAcftNameLabel'),
        'icaoCode'           => getNodeText($doc, 'MainContent_wsICAOLabel'),
        'initialClimbIAS'    => getNodeText($doc, 'wsINVCLLiteral'),
        'initialClimbROC'    => getNodeText($doc, 'wsINROCLiteral'),
        'climb150IAS'        => getNodeText($doc, 'wsIASVCLLiteral'),
        'climb150ROC'        => getNodeText($doc, 'wsIASROCLiteral'),
        'climb240IAS'        => getNodeText($doc, 'wsIASVCLLiteral2'),
        'climb240ROC'        => getNodeText($doc, 'wsIASROC2Literal'),
        'machClimbMACH'      => getNodeText($doc, 'wsMACHVCLLiteral'),
        'machClimbROC'       => getNodeText($doc, 'wsMACHROCLiteral'),
        'cruiseTAS'          => getNodeText($doc, 'wsVCSknotsLiteral'),
        'cruiseMACH'         => getNodeText($doc, 'wsVCSmachLiteral'),
        'cruiseCeiling'      => getNodeText($doc, 'wsCeilingLiteral'),
        'cruiseRange'        => getNodeText($doc, 'wsRangeLiteral'),
        'initialDescentMACH' => getNodeText($doc, 'wsMACHVDESCLiteral'),
        'initialDescentROD'  => getNodeText($doc, 'wsMACHRODLiteral'),
        'descentIAS'         => getNodeText($doc, 'wsIASVDESCLiteral'),
        'descentROD'         => getNodeText($doc, 'wsIASRODLiteral'),
        'approachIAS'        => getNodeText($doc, 'wsBelowVDESCLiteral'),
        'approachROD'        => getNodeText($doc, 'wsBelowRODLiteral')
    ];

    $data = [];

    foreach ($raw as $field => $value) {
        $value = $value === null ? '' : trim($value);
        if ($value === '' || strtolower($value) === 'no data') {
            $data[$field] = null;
            continue;
        }

        if ($field === 'cruiseCeiling') {
            $val = preg_replace('/[^0-9FL]/i', '', $value);

            if (preg_match('/^FL(\d+)$/i', $val, $m)) {
                // Explicit FLxxx format
                $data[$field] = (int)$m[1];
            } elseif (is_numeric($val)) {
                // Eurocontrol provides Ceiling in FL (see wsCeilingLiteral + unit "FL").
                // Keep values like "200" as FL200. Only treat very large numbers as feet.
                $n = (int)$val;
                if ($n >= 1000) {
                    // Likely feet (e.g., 41000) -> convert to FL
                    $data[$field] = (int)round($n / 100);
                } else {
                    // Already FL (e.g., 200)
                    $data[$field] = $n;
                }
            } else {
                $data[$field] = null;
            }
            continue;
        }

        if ($field === 'cruiseRange') {
            $val = preg_replace('/[^0-9.]/', '', $value);
            $data[$field] = is_numeric($val) ? (float)$val : null;
            continue;
        }

        $data[$field] = is_numeric($value) ? $value + 0 : $value;
    }

    // Determine type (mach vs knots)
    $hasMachClimb = !is_null($data['machClimbMACH']) && !is_null($data['machClimbROC']) && $data['machClimbMACH'] > 0;
    $data['type'] = $hasMachClimb ? 'mach' : 'knots';

    return $data;
}
?>
<a id="top"></a>
<script>
	// Single source of truth for scrolling on this page:
	// ?jump=top    -> #top
	// ?jump=footer -> #footer-start
	(function () {
		try {
			const qs = new URLSearchParams(window.location.search);
			const jump = qs.get('jump');

			if (jump === 'top') {
				window.location.hash = 'top';
				window.scrollTo(0, 0);
				setTimeout(function () { window.scrollTo(0, 0); }, 50);
				setTimeout(function () { window.scrollTo(0, 0); }, 250);
				return;
			}

			if (jump === 'footer') {
				window.location.hash = 'footer-start';
				return;
			}
		} catch (e) {}
	})();
</script>
<div style="margin-bottom:20px;">
    <a href="?" 
       style="display:inline-block; padding:12px 20px; background:#667eea; color:#fff; border-radius:8px; text-decoration:none; font-size:18px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        ← <?php echo $lang['back_to_scheduler']; ?>
    </a>
</div>

<h1 style="text-align:center;"><?php echo $lang['add_aircraft_search_title']; ?></h1>

<?php if (!empty($pre_title_message_html) || !empty($top_messages_html)): ?>
    <script>window.scrollTo(0,0);</script>
<?php endif; ?>

<?php if (!empty($pre_title_message_html)): ?>
    <?php echo $pre_title_message_html; ?>
<?php endif; ?>

<?php if (!empty($top_messages_html)): ?>
    <?php echo $top_messages_html; ?>
<?php endif; ?>

<?php if (isset($_GET['aircraft_added']) && $_GET['aircraft_added'] === '1'): ?>
    <div class='result-box' style='margin-top:15px; background:#e6ffed; border-left:4px solid #48bb78;'>
        <strong style='color:#1f7a3a;'><?php echo htmlspecialchars($lang['add_aircraft_saved_ok']); ?></strong>
        <?php if (isset($_GET['aircraft_name']) && $_GET['aircraft_name'] !== ''): ?>
            <span style='color:#1f7a3a;'> <?php echo htmlspecialchars((string)$_GET['aircraft_name']); ?></span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="info-box"><?php echo $lang['eurocontrol_performance_info']; ?></div>

<br>
<div class="info-box"><?php echo $lang['manual_performance_info']; ?></div>
<br>
<!-- ==================== SECTION 1 – ICAO & GENERIC SEARCH ==================== -->
<div class="popup-section" style="border:2px solid #cbd5e0; border-radius:8px; background:#f7fafc; padding:20px; margin-bottom:30px;">
    <!-- ICAO SEARCH -->
    <h2><?php echo $lang['search_by_icao']; ?></h2>
    <form method="GET"
          action="#"
          onsubmit="event.preventDefault(); 
                   var icao = document.getElementById('icao_code').value; 
                   var url = 'https://learningzone.eurocontrol.int/ilp/customs/ATCPFDB/details.aspx?ICAO=' + encodeURIComponent(icao) + '&ICAOFilter=' + encodeURIComponent(icao); 
                   document.getElementById('extract_url').value = url; 
                   document.getElementById('extract_form').submit();">
        <input type="text"
               id="icao_code"
               name="ICAO"
               placeholder="e.g. A320"
               required>
        <button type="submit">🔎 <?php echo $lang['fetch_icao_button']; ?></button>
    </form>

    <!-- GENERIC SEARCH -->
    <h2 style="margin-top:20px;"><?php echo $lang['generic_search']; ?></h2>
    <form method="GET"
          action="https://learningzone.eurocontrol.int/ilp/customs/ATCPFDB/default.aspx"
          target="_blank">
        <input type="text"
               id="generic_query"
               name="NameFilter"
               placeholder="manufacturer, model, variant…"
               required>
        <button type="submit">🔎 <?php echo $lang['generic_search_button']; ?></button>
    </form>
    <br><h2><center><?php echo $lang['obtain_data_eurocontrol']; ?></center></h2>
    <form id="extract_form" method="post" style="margin-bottom:15px;">
        <input type="hidden" name="action" value="extract">
        <input id="extract_url" type="text"
               name="url"
               size="80"
               placeholder="https://…"
               required>
        <button type="submit">
            <?php echo $lang['obtain_performance']; ?>
        </button>
    </form>
</div>

<!-- ======== VISUAL BREAK BETWEEN SECTION 1 AND SECTION 2 ======== -->
<div style="margin:40px 0; border-top:4px double #4a5568;"></div>

<!-- ==================== SECTION 2 – ADD AIRCRAFT ==================== -->

<h1 style="text-align:center;"><?php echo $lang['add_aircraft_title']; ?></h1>

<div class="search-section" style="border:2px solid #cbd5e0; border-radius:8px; background:#edf2f7; padding:20px;">

    <?php
    // -----------------------------------------------------------------
    // Always initialise $data as an empty array so every field exists.
    // -----------------------------------------------------------------
    // $data, $showReviewWarning, and $action are already handled BEFORE HTML rendering.
    // Keep them here only for compatibility with the rest of the file.
    $data = $data ?? [];
    $showReviewWarning = $showReviewWarning ?? false;

    $action = $action ?? ($_POST['action'] ?? '');

    /* --------------------------------------------------------------
       SAVE AIRCRAFT (action=accept_extract)
       -------------------------------------------------------------- */
    if (false && $action === 'accept_extract') {

        $newKey = strtoupper(trim((string)($_POST['aircraft_display'] ?? '')));

        if ($newKey === '') {
            echo "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_missing_name']) . "
                  </div>";
            exit;
        }

        $aircraftFile = __DIR__ . '/../aircrafts/aircraft.php';

        if (!file_exists($aircraftFile)) {
            $pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_found']) . "
                  </div>";
            $action = ''; // stop processing this POST
        }
        if ($action === 'accept_extract' && $pre_title_message_html === '' && !is_readable($aircraftFile)) {
            $pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_readable']) . "
                  </div>";
            $action = '';
        }
        if ($action === 'accept_extract' && $pre_title_message_html === '' && !is_writable($aircraftFile)) {
            $pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_not_writable']) . "
                  </div>";
            $action = '';
        }

        $aircraft_list_current = require $aircraftFile;
        if ($action === 'accept_extract' && $pre_title_message_html === '' && !is_array($aircraft_list_current)) {
            $pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_file_invalid']) . "
                  </div>";
            $action = '';
        }

        // If we decided to stop processing (file errors etc.), stop NOW and render the page normally.
        if ($action !== 'accept_extract') {
            echo "<script>window.scrollTo(0,0);</script>";
        } else {

        $exists = array_key_exists($newKey, $aircraft_list_current);
        $overwriteConfirmed = (isset($_POST['overwrite_confirm']) && $_POST['overwrite_confirm'] === '1');

        $DBG_TOP = "newKey=" . $newKey . "\nexists=" . ($exists ? "1" : "0") . "\noverwriteConfirmed=" . ($overwriteConfirmed ? "1" : "0");

        if ($exists && !$overwriteConfirmed) {
            $DBG_TOP .= "\nHIT: exists && !overwriteConfirmed";

            $msg = $lang['add_aircraft_exists_overwrite'] ?? ($lang['missing_translation'] ?? '');
            $tAccept = $lang['accept'] ?? ($lang['missing_translation'] ?? '');
            $tCancel = $lang['cancel'] ?? ($lang['missing_translation'] ?? '');

            $top_messages_html .= "<div class='error-box' style='background:#fff3cd; border-left:4px solid #ffc107; color:#856404; margin-top:15px;'>
                    <strong>" . htmlspecialchars($msg) . "</strong><br><br>
                    <form method='post' style='display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:10px;'>
                        <input type='hidden' name='action' value='accept_extract'>
                        <input type='hidden' name='overwrite_confirm' value='1'>
                        <input type='hidden' name='aircraft_display' value='" . htmlspecialchars($newKey) . "'>";

            $fields = [
                'type',
                'initialClimbIAS','initialClimbROC',
                'climb150IAS','climb150ROC',
                'climb240IAS','climb240ROC',
                'machClimbMACH','machClimbROC',
                'cruiseTAS','cruiseMACH','cruiseCeiling','cruiseRange',
                'initialDescentMACH','initialDescentROD',
                'descentIAS','descentROD',
                'approachIAS','approachROD'
            ];

            foreach ($fields as $f) {
                $val = (string)($_POST[$f] ?? '');
                $top_messages_html .= "<input type='hidden' name='" . htmlspecialchars($f) . "' value='" . htmlspecialchars($val) . "'>";
            }

            $top_messages_html .= "      <button type='submit'
                                style='width:auto; padding:10px 18px; background:#48bb78; color:white; border:none; border-radius:5px; cursor:pointer;'>
                            {$tAccept}
                        </button>
                        <button type='button'
                                onclick=\"window.location.href='?add_aircraft=1';\"
                                style='width:auto; padding:10px 18px; background:#e53e3e; color:white; border:none; border-radius:5px; cursor:pointer;'>
                            {$tCancel}
                        </button>
                    </form>
                  </div>";

            // Stop processing and render page normally (message will be at the top)
            echo "<script>window.scrollTo(0,0);</script>";
            goto ADD_AIRCRAFT_END_ACCEPT_EXTRACT;
        }

        $numOrNull = function($v) {
            if ($v === null) return null;
            $v = trim((string)$v);
            if ($v === '') return null;
            return is_numeric($v) ? ($v + 0) : null;
        };

        $newProfile = [
            'type'               => (string)($_POST['type'] ?? 'mach'),
            'initialClimbIAS'    => $numOrNull($_POST['initialClimbIAS'] ?? null),
            'initialClimbROC'    => $numOrNull($_POST['initialClimbROC'] ?? null),
            'climb150IAS'        => $numOrNull($_POST['climb150IAS'] ?? null),
            'climb150ROC'        => $numOrNull($_POST['climb150ROC'] ?? null),
            'climb240IAS'        => $numOrNull($_POST['climb240IAS'] ?? null),
            'climb240ROC'        => $numOrNull($_POST['climb240ROC'] ?? null),
            'machClimbMACH'      => $numOrNull($_POST['machClimbMACH'] ?? null),
            'machClimbROC'       => $numOrNull($_POST['machClimbROC'] ?? null),
            'cruiseTAS'          => $numOrNull($_POST['cruiseTAS'] ?? null),
            'cruiseMACH'         => $numOrNull($_POST['cruiseMACH'] ?? null),
            'cruiseCeiling'      => $numOrNull($_POST['cruiseCeiling'] ?? null),
            'cruiseRange'        => $numOrNull($_POST['cruiseRange'] ?? null),
            'initialDescentMACH' => $numOrNull($_POST['initialDescentMACH'] ?? null),
            'initialDescentROD'  => $numOrNull($_POST['initialDescentROD'] ?? null),
            'descentIAS'         => $numOrNull($_POST['descentIAS'] ?? null),
            'descentROD'         => $numOrNull($_POST['descentROD'] ?? null),
            'approachIAS'        => $numOrNull($_POST['approachIAS'] ?? null),
            'approachROD'        => $numOrNull($_POST['approachROD'] ?? null),
        ];

        $aircraft_list_current[$newKey] = $newProfile;

        uksort($aircraft_list_current, function ($a, $b) {
            $na = (string)$a;
            $nb = (string)$b;

            // Treat Boeing-style suffixes as series: -8 => -800, -9 => -900, -10 => -1000
            $na = preg_replace('/-(10)(?!\d)/', '-1000', $na);
            $nb = preg_replace('/-(10)(?!\d)/', '-1000', $nb);
            $na = preg_replace('/-(8)(?!\d)/', '-800', $na);
            $nb = preg_replace('/-(8)(?!\d)/', '-800', $nb);
            $na = preg_replace('/-(9)(?!\d)/', '-900', $na);
            $nb = preg_replace('/-(9)(?!\d)/', '-900', $nb);

            return strnatcasecmp($na, $nb);
        });

        $out = "<?php\n\n";
        $out .= "\$aircraft_list = [\n";
        foreach ($aircraft_list_current as $name => $profile) {
            $out .= "\t" . var_export($name, true) . " => " . var_export($profile, true) . ",\n";
        }
        $out .= "\t];\n\n";
        $out .= "\treturn \$aircraft_list;\n";

        $bytes = @file_put_contents($aircraftFile, $out);
        if ($bytes === false) {
            $pre_title_message_html = "<div class='error-box' style='margin-top:15px;'>
                    <strong>⚠️ " . htmlspecialchars($lang['error'] ?? ($lang['missing_translation'] ?? '')) . ":</strong> " . htmlspecialchars($lang['add_aircraft_error_write_failed']) . "
                  </div>";
            $action = '';
        }

        // After save, reload the page (JS redirect, not PHP header) so the success message appears under the title
        // and the form is clean for adding another aircraft.
        $redir = '?add_aircraft=1&aircraft_added=1&aircraft_name=' . rawurlencode($newKey) . '&jump=top';
		echo "<script>
			window.location.replace(" . json_encode($redir) . ");
		</script>";
		return;

        } // end: else {  (the branch where we continue processing accept_extract)

        ADD_AIRCRAFT_END_ACCEPT_EXTRACT:
    }

    if (false && $action === 'extract' && !empty($_POST['url'])) {
        $url  = trim($_POST['url']);
        $html = @file_get_contents($url);

        if ($html === false) {
            echo "<p style='color:red;'>{$lang['error_eurocontrol_fetch']}</p>";
        } else {
            // Redirect to avoid POST-resubmission and to make scrolling deterministic
            echo "<script>
                window.location.replace(" . json_encode('?add_aircraft=1&jump=footer') . ");
            </script>";
            return;
        }
    }

    // Helper – render two readonly inputs side‑by‑side (if $value2 is null the second input stays empty)
    $renderPair = function(string $name1, string $label1, $value1,
                           string $name2 = '', string $label2 = '', $value2 = null) {
        $v1 = htmlspecialchars($value1 ?? '');
        $v2 = htmlspecialchars($value2 ?? '');
        // flex container – keeps original vertical spacing
        echo "<div style='display:flex; gap:20px; margin-bottom:8px; align-items:center;'>
                <div>
                    <label>{$label1}:</label>
                    <input type='text' name='{$name1}' value='{$v1}' readonly style='width:200px;' />
                </div>";
        if ($name2 !== '') {
            echo "    <div>
                    <label>{$label2}:</label>
                    <input type='text' name='{$name2}' value='{$v2}' readonly style='width:200px;' />
                </div>";
        }
        echo "</div>";
    };

    // -----------------------------------------------------------------
    // 1 & 2 – Initial climb (to 5000 ft)
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['initial_climb']}</strong></div><br>";
        $renderPair('initialClimbIAS', $lang['FinitialClimbIAS'], $data['initialClimbIAS'] ?? null,
                    'initialClimbROC', $lang['initialClimbROC'], $data['initialClimbROC'] ?? null);
    // -----------------------------------------------------------------
    // 3 & 4 – Climb (to FL150)
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['climb_fl150']}</strong></div><br>";
        $renderPair('climb150IAS', $lang['climb150IAS'], $data['climb150IAS'] ?? null,
                    'climb150ROC', $lang['climb150ROC'], $data['climb150ROC'] ?? null);

    // -----------------------------------------------------------------
    // 5 & 6 – Climb (to FL240)
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['climb_fl240']}</strong></div><br>";
        $renderPair('climb240IAS', $lang['climb240IAS'], $data['climb240IAS'] ?? null,
                    'climb240ROC', $lang['climb240ROC'], $data['climb240ROC'] ?? null);

    // -----------------------------------------------------------------
    // 7 & 8 – MACH climb
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['mach_climb']}</strong></div><br>";
        $renderPair('machClimbMACH', $lang['machClimbMACH'], $data['machClimbMACH'] ?? null,
                    'machClimbROC', $lang['machClimbROC'], $data['machClimbROC'] ?? null);

    // -----------------------------------------------------------------
    // 9 – 12 – Cruise
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['cruise']}</strong></div><br>";
        $renderPair('cruiseTAS', $lang['cruiseTAS'], $data['cruiseTAS'] ?? null,
                    'cruiseMACH', $lang['cruiseMACH'], $data['cruiseMACH'] ?? null);
        $renderPair('cruiseCeiling', $lang['cruiseCeiling'], $data['cruiseCeiling'] ?? null,
                    'cruiseRange', $lang['cruiseRange'], $data['cruiseRange'] ?? null);

    // -----------------------------------------------------------------
    // 13 & 14 – Initial descent (to FL240)
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['initial_descent']}</strong></div><br>";
        $renderPair('initialDescentMACH', $lang['initialDescentMACH'], $data['initialDescentMACH'] ?? null,
                    'initialDescentROD', $lang['initialDescentROD'], $data['initialDescentROD'] ?? null);

    // -----------------------------------------------------------------
    // 15 & 16 – Descent (to FL100)
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['descent']}</strong></div><br>";
        $renderPair('descentIAS', $lang['descentIAS'], $data['descentIAS'] ?? null,
                    'descentROD', $lang['descentROD'], $data['descentROD'] ?? null);

    // -----------------------------------------------------------------
    // 17 & 18 – Approach
    // -----------------------------------------------------------------
    echo "<div><strong>{$lang['approach']}</strong></div><br>";
        $renderPair('approachIAS', $lang['approachIAS'], $data['approachIAS'] ?? null,
                    'approachROD', $lang['approachROD'], $data['approachROD'] ?? null);

    // -----------------------------------------------------------------
    // Aircraft details (Manufacturer + Name + ICAO) - single editable field
    // -----------------------------------------------------------------
    $manufacturer = trim((string)($data['manufacturer'] ?? ''));
    $aircraftName = trim((string)($data['aircraftName'] ?? ''));
    $icaoCode     = trim((string)($data['icaoCode'] ?? ''));

    $acftLine = trim($manufacturer . ' ' . $aircraftName);
    if ($icaoCode !== '') {
        $acftLine = trim($acftLine . ' (' . $icaoCode . ')');
    }

    $acftLineEsc = htmlspecialchars($acftLine);

    $aircraftLabelEditable = $lang['aircraft_label'] ?? ($lang['missing_translation'] ?? '');

    echo "<div style='display:flex; justify-content:center; margin: 10px 0 18px 0;'>
            <div style='text-align:center;'>
                <label style='font-weight:700; font-size:16px; display:block; margin-bottom:6px;'>{$aircraftLabelEditable}</label>
                <input type='text'
                       id='aircraft_display_input'
                       name='aircraft_display'
                       value='{$acftLineEsc}'
                       style='width:520px; max-width:100%; font-size:18px; font-weight:700; text-align:center; padding:10px 12px; border:2px solid #cbd5e0; border-radius:8px;' />
            </div>
          </div>";
    ?>
</div>

<?php
    if ($showReviewWarning) {
        $tExtracted = $lang['extracted_data'] ?? ($lang['missing_translation'] ?? '');
        $tReview    = $lang['add_aircraft_review_warning'] ?? '';
        $tAccept    = $lang['accept'] ?? ($lang['missing_translation'] ?? '');
        $tCancel    = $lang['cancel'] ?? ($lang['missing_translation'] ?? '');

        echo "<div id='addAircraftReviewWarning' class='error-box' style='background:#fff3cd; border-left:4px solid #ffc107; color:#856404; margin-top:15px;'>
                <strong>{$tExtracted}:</strong><br><br>";

        foreach ($data as $k => $v) {
            $kEsc = htmlspecialchars((string)$k);
            if (is_array($v)) {
                $vEsc = htmlspecialchars(json_encode($v));
            } elseif ($v === null) {
                $vEsc = 'null';
            } else {
                $vEsc = htmlspecialchars((string)$v);
            }
            echo "{$kEsc}: {$vEsc}<br>";
        }

        echo "<br><strong>{$tReview}</strong><br><br>";

        echo "</div>";
    }
?>
<div style="margin-top:30px; display:grid; grid-template-columns: 1fr 1fr; gap:12px; max-width:420px; margin-left:auto; margin-right:auto;">

<form method="post" style="margin:0;" onsubmit="var src=document.getElementById('aircraft_display_input'); var dst=document.getElementById('aircraft_display_hidden'); if(src && dst){ dst.value = src.value; }">
    <input type="hidden" name="action" value="accept_extract">

    <input type="hidden" id="aircraft_display_hidden" name="aircraft_display" value="">
    <input type="hidden" name="type" value="<?php echo htmlspecialchars((string)($data['type'] ?? '')); ?>">

    <input type="hidden" name="initialClimbIAS" value="<?php echo htmlspecialchars((string)($data['initialClimbIAS'] ?? '')); ?>">
    <input type="hidden" name="initialClimbROC" value="<?php echo htmlspecialchars((string)($data['initialClimbROC'] ?? '')); ?>">

    <input type="hidden" name="climb150IAS" value="<?php echo htmlspecialchars((string)($data['climb150IAS'] ?? '')); ?>">
    <input type="hidden" name="climb150ROC" value="<?php echo htmlspecialchars((string)($data['climb150ROC'] ?? '')); ?>">

    <input type="hidden" name="climb240IAS" value="<?php echo htmlspecialchars((string)($data['climb240IAS'] ?? '')); ?>">
    <input type="hidden" name="climb240ROC" value="<?php echo htmlspecialchars((string)($data['climb240ROC'] ?? '')); ?>">

    <input type="hidden" name="machClimbMACH" value="<?php echo htmlspecialchars((string)($data['machClimbMACH'] ?? '')); ?>">
    <input type="hidden" name="machClimbROC" value="<?php echo htmlspecialchars((string)($data['machClimbROC'] ?? '')); ?>">

    <input type="hidden" name="cruiseTAS" value="<?php echo htmlspecialchars((string)($data['cruiseTAS'] ?? '')); ?>">
    <input type="hidden" name="cruiseMACH" value="<?php echo htmlspecialchars((string)($data['cruiseMACH'] ?? '')); ?>">
    <input type="hidden" name="cruiseCeiling" value="<?php echo htmlspecialchars((string)($data['cruiseCeiling'] ?? '')); ?>">
    <input type="hidden" name="cruiseRange" value="<?php echo htmlspecialchars((string)($data['cruiseRange'] ?? '')); ?>">

    <input type="hidden" name="initialDescentMACH" value="<?php echo htmlspecialchars((string)($data['initialDescentMACH'] ?? '')); ?>">
    <input type="hidden" name="initialDescentROD" value="<?php echo htmlspecialchars((string)($data['initialDescentROD'] ?? '')); ?>">

    <input type="hidden" name="descentIAS" value="<?php echo htmlspecialchars((string)($data['descentIAS'] ?? '')); ?>">
    <input type="hidden" name="descentROD" value="<?php echo htmlspecialchars((string)($data['descentROD'] ?? '')); ?>">

    <input type="hidden" name="approachIAS" value="<?php echo htmlspecialchars((string)($data['approachIAS'] ?? '')); ?>">
    <input type="hidden" name="approachROD" value="<?php echo htmlspecialchars((string)($data['approachROD'] ?? '')); ?>">

    <button type="submit"
            style="width:100%; padding:10px 20px; background:#48bb78; color:white; border:none; border-radius:5px; cursor:pointer;">
        ✈️ <?php echo $lang['add_aircraft']; ?>
    </button>
</form>

    <button type="button" onclick="window.location.href='?add_aircraft=1';"
            style="width:100%; padding:10px 20px; background:#e53e3e; color:white; border:none; border-radius:5px; cursor:pointer;">
        🔃 <?php echo $lang['reset']; ?>
    </button>

</div>

<div id="footer-start"></div>
<script>
    function hideAddAircraftWarning() {
        var box = document.getElementById('addAircraftReviewWarning');
        if (box) {
            box.style.display = 'none';
        }
    }
</script>

<br>
<div style="margin-bottom:20px;">
    <a href="?" 
       style="display:inline-block; padding:12px 20px; background:#667eea; color:#fff; border-radius:8px; text-decoration:none; font-size:18px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        ← <?php echo $lang['back_to_scheduler']; ?>
    </a>
</div>
