<?php
include_once 'common.php';
include_once '../app/custom.php';

$model = null;
if (isset($_REQUEST['data']['model'])) $model = $_REQUEST['data']['model'];
$mode = null;
if (isset($_REQUEST['data']['mode'])) $mode = $_REQUEST['data']['mode'];
$source = 'default';
if (isset($_REQUEST['data']['source'])) $source = $_REQUEST['data']['source'];
$value = null;
if (isset($_REQUEST['data']['value'])) $value = $_REQUEST['data']['value'];
$format = 'json';
if (isset($_REQUEST['data']['format'])) $filter = $_REQUEST['data']['format'];

if (is_null($mode)) die('{"status":"Error","message":"The \'mode\' must be set."}');

$result = '';

if ($mode == 'login') $result = user_login($value, $format);
if ($mode == 'strt') $result = application_start($value, $format);
if ($loggedin) {
	if ($mode == 'menu') $result = menu_view($model, $format);
	if ($mode == 'view') $result = model_view($model, $source, $format);
	if ($mode == 'srch') $result = model_search($model, $source, $value, $format);
	if ($mode == 'ovvw') $result = model_overview($model, $format);
	if ($mode == 'actn') $result = model_action($model, $value, $format);
	if ($mode == 'edit') $result = model_edit($model, $format);
	if ($mode == 'get') $result = model_get($model, $value, $format);
	if ($mode == 'set') $result = model_set($model, $value, $format);
	if ($mode == 'ping') $result = model_ping();
	if ($mode == 'cstm') $result = do_custom_event($model);
}

function do_custom_event($ev) {
	if ($ev == 'verify_db') return model_check_entiredb();
}

if ($format == 'json') {
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1998 05:00:00 GMT');
	header('Content-type: application/json');
	echo $result;
}


?>