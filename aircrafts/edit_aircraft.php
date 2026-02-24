<?php
// edit_aircraft.php – fragment included inside index.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../preferences.php';
require_once __DIR__ . '/../aircrafts/aircraft.php';

$langResult = handleLanguageSelection();
$lang = $langResult['lang'];
$current_language = $langResult['current_language'];

$aircraftFile = __DIR__ . '/aircraft.php';

// Load list (aircraft.php returns the array)
$aircraft_list_current = require $aircraftFile;
if (!is_array($aircraft_list_current)) {
    $aircraft_list_current = [];
}

$action = $_POST['action'] ?? '';
$selected = $_POST['selected_aircraft'] ?? ($_GET['selected_aircraft'] ?? '');
$selected = (string)$selected;

$message = '';
$message_is_error = false;

$numOrNull = function($v) {
    if ($v === null) return null;
    $v = trim((string)$v);
    if ($v === '') return null;
    return is_numeric($v) ? ($v + 0) : null;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'save_aircraft') {

        $oldKey = (string)($_POST['old_key'] ?? '');
        $newKey = strtoupper(trim((string)($_POST['aircraft_name'] ?? '')));

        if ($oldKey === '' || !array_key_exists($oldKey, $aircraft_list_current)) {
            $message = $lang['error'] ?? 'Error';
            $message_is_error = true;
        } elseif ($newKey === '') {
            $message = $lang['add_aircraft_error_missing_name'] ?? ($lang['error'] ?? 'Error');
            $message_is_error = true;
        } else {

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

            // If key changed, handle potential conflict
            if ($newKey !== $oldKey && array_key_exists($newKey, $aircraft_list_current)) {
                $message = $lang['add_aircraft_exists_overwrite'] ?? ($lang['error'] ?? 'Error');
                $message_is_error = true;
            } else {
                unset($aircraft_list_current[$oldKey]);
                $aircraft_list_current[$newKey] = $newProfile;

                ksort($aircraft_list_current, SORT_STRING | SORT_FLAG_CASE);

                $out = "<?php\n\n";
                $out .= "\$aircraft_list = [\n";
                foreach ($aircraft_list_current as $name => $profile) {
                    $out .= "\t" . var_export($name, true) . " => " . var_export($profile, true) . ",\n";
                }
                $out .= "\t];\n\n";
                $out .= "\treturn \$aircraft_list;\n";

                $bytes = @file_put_contents($aircraftFile, $out);
                if ($bytes === false) {
                    $message = $lang['add_aircraft_error_write_failed'] ?? ($lang['error'] ?? 'Error');
                    $message_is_error = true;
                } else {
                    $message = $lang['aircraft_saved_ok'] ?? 'Saved';
                    $message_is_error = false;
                    $selected = $newKey;
                }
            }
        }

    } elseif ($action === 'delete_aircraft') {

        $key = (string)($_POST['old_key'] ?? '');
        if ($key !== '' && array_key_exists($key, $aircraft_list_current)) {

            unset($aircraft_list_current[$key]);

            ksort($aircraft_list_current, SORT_STRING | SORT_FLAG_CASE);

            $out = "<?php\n\n";
            $out .= "\$aircraft_list = [\n";
            foreach ($aircraft_list_current as $name => $profile) {
                $out .= "\t" . var_export($name, true) . " => " . var_export($profile, true) . ",\n";
            }
            $out .= "\t];\n\n";
            $out .= "\treturn \$aircraft_list;\n";

            $bytes = @file_put_contents($aircraftFile, $out);
            if ($bytes === false) {
                $message = $lang['add_aircraft_error_write_failed'] ?? ($lang['error'] ?? 'Error');
                $message_is_error = true;
            } else {
                $message = $lang['aircraft_deleted_ok'] ?? 'Deleted';
                $message_is_error = false;
                $selected = '';
            }
        } else {
            $message = $lang['error'] ?? 'Error';
            $message_is_error = true;
        }
    }
}

$selectedProfile = null;
if ($selected !== '' && isset($aircraft_list_current[$selected])) {
    $selectedProfile = $aircraft_list_current[$selected];
}
?>
<div style="margin-bottom:20px;">
    <a href="?"
       style="display:inline-block; padding:12px 20px; background:#667eea; color:#fff; border-radius:8px; text-decoration:none; font-size:18px; font-weight:600; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
        ← <?php echo $lang['back_to_scheduler']; ?>
    </a>
</div>

<h1 style="text-align:center;"><?php echo $lang['edit_aircraft_title']; ?></h1>


<?php if ($message !== ''): ?>
    <?php if ($message_is_error): ?>
        <div class="error-box" style="margin-top:15px;">
            <strong>⚠️ <?php echo $lang['error']; ?>:</strong> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php else: ?>
        <div class="result-box" style="margin-top:15px; background:#e6ffed; border-left:4px solid #48bb78;">
            <strong style="color:#1f7a3a;"><?php echo htmlspecialchars($message); ?></strong>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="info-box"><?php echo $lang['edit_aircraft_tip']; ?></div>

<div class="search-section" style="border:2px solid #cbd5e0; border-radius:8px; background:#edf2f7; padding:20px; margin-top:20px;">

    <form method="post" style="margin-bottom:20px;">
        <label for="selected_aircraft"><?php echo $lang['select_aircraft']; ?>:</label>
        <select id="selected_aircraft" name="selected_aircraft" style="max-width:520px;">
            <option value=""></option>
            <?php foreach ($aircraft_list_current as $name => $profile): ?>
                <option value="<?php echo htmlspecialchars($name); ?>" <?php echo ($selected === $name) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" style="max-width:220px;"><?php echo $lang['select_aircraft']; ?></button>
    </form>

    <?php if ($selectedProfile): ?>

    <form method="post">
        <input type="hidden" name="action" value="save_aircraft">
        <input type="hidden" name="old_key" value="<?php echo htmlspecialchars($selected); ?>">

        <div class="form-group">
            <label for="aircraft_name"><?php echo $lang['edit_aircraft_field_name']; ?>:</label>
            <input type="text" id="aircraft_name" name="aircraft_name" value="<?php echo htmlspecialchars($selected); ?>" maxlength="200" style="max-width:520px;">
        </div>

        <div class="form-group">
            <label for="type">type:</label>
            <select id="type" name="type" style="max-width:220px;">
                <option value="mach" <?php echo (($selectedProfile['type'] ?? '') === 'mach') ? 'selected' : ''; ?>>mach</option>
                <option value="knots" <?php echo (($selectedProfile['type'] ?? '') === 'knots') ? 'selected' : ''; ?>>knots</option>
            </select>
        </div>

        <?php
            $fields = [
                'initialClimbIAS','initialClimbROC',
                'climb150IAS','climb150ROC',
                'climb240IAS','climb240ROC',
                'machClimbMACH','machClimbROC',
                'cruiseTAS','cruiseMACH','cruiseCeiling','cruiseRange',
                'initialDescentMACH','initialDescentROD',
                'descentIAS','descentROD',
                'approachIAS','approachROD'
            ];
        ?>

        <div class="form-row" style="grid-template-columns: 1fr 1fr;">
        <?php foreach ($fields as $f): ?>
            <div class="form-group">
                <label for="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?>:</label>
                <input type="text"
                       id="<?php echo htmlspecialchars($f); ?>"
                       name="<?php echo htmlspecialchars($f); ?>"
                       value="<?php echo htmlspecialchars((string)($selectedProfile[$f] ?? '')); ?>"
                       style="max-width:220px;">
            </div>
        <?php endforeach; ?>
        </div>

        <div style="margin-top:20px; display:grid; grid-template-columns: 1fr 1fr; gap:12px; max-width:420px;">
            <button type="submit"
                    style="width:100%; padding:10px 20px; background:#48bb78; color:white; border:none; border-radius:5px; cursor:pointer;">
                💾 <?php echo $lang['save_changes']; ?>
            </button>
    </form>

            <form method="post" onsubmit="return showConfirmDeleteAircraftModal(event);" style="margin:0;">
                <input type="hidden" name="action" value="delete_aircraft">
                <input type="hidden" name="old_key" value="<?php echo htmlspecialchars($selected); ?>">
                <button type="submit"
                        style="width:100%; padding:10px 20px; background:#e53e3e; color:white; border:none; border-radius:5px; cursor:pointer;">
                    🗑️ <?php echo $lang['delete_aircraft']; ?>
                </button>
            </form>
        </div>

<script>
function showConfirmDeleteAircraftModal(e) {
    if (e) e.preventDefault();

    var msg = <?php echo json_encode((string)($lang['confirm_delete_aircraft'] ?? 'Are you sure?')); ?>;
    var tAccept = <?php echo json_encode((string)($lang['accept'] ?? 'Accept')); ?>;
    var tCancel = <?php echo json_encode((string)($lang['cancel'] ?? 'Cancel')); ?>;
    var tNote = <?php echo json_encode((string)($lang['note'] ?? 'NOTE')); ?>;

    // Build modal
    var backdrop = document.createElement('div');
    backdrop.id = 'confirmDeleteBackdrop';
    backdrop.style.cssText = 'position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:99999; display:flex; align-items:center; justify-content:center; padding:20px;';

    backdrop.innerHTML =
        "<div style='background:#fff; width:100%; max-width:520px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.35); overflow:hidden;'>" +
            "<div style='padding:16px 18px; background:#fff3cd; border-bottom:1px solid rgba(0,0,0,0.08);'>" +
                "<div style='font-weight:700; color:#856404; font-size:16px;'>⚠️ " + escapeHtml(tNote) + "</div>" +
            "</div>" +
            "<div style='padding:18px; color:#333; font-size:14px; line-height:1.5;'>" +
                escapeHtml(msg).replace(/\\n/g, '<br>') +
            "</div>" +
            "<div style='display:flex; gap:12px; justify-content:flex-end; padding:0 18px 18px 18px;'>" +
                "<button type='button' id='confirmDeleteCancelBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#e53e3e; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>" +
                    escapeHtml(tCancel) +
                "</button>" +
                "<button type='button' id='confirmDeleteAcceptBtn' style='width:auto; margin-top:0; padding:10px 16px; background:#48bb78; border:none; border-radius:8px; color:#fff; font-weight:700; cursor:pointer;'>" +
                    escapeHtml(tAccept) +
                "</button>" +
            "</div>" +
        "</div>";

    document.body.appendChild(backdrop);

    function closeModal() {
        if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
    }

    function onEsc(ev) {
        if (ev && ev.key === 'Escape') {
            document.removeEventListener('keydown', onEsc);
            closeModal();
        }
    }
    document.addEventListener('keydown', onEsc);

    var acceptBtn = document.getElementById('confirmDeleteAcceptBtn');
    var cancelBtn = document.getElementById('confirmDeleteCancelBtn');

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            document.removeEventListener('keydown', onEsc);
            closeModal();
            // Submit the delete form that triggered the modal
            if (e && e.target) e.target.submit();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            document.removeEventListener('keydown', onEsc);
            closeModal();
        });
    }

    return false;
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;')
        .replace(/'/g,'&#039;');
}
</script>

    <?php endif; ?>

</div>
