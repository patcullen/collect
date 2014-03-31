<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

$schema = null;
function load_schema() {
	global $schema;
	$schema = json_decode(file_get_contents('../app/schema.json'));
} load_schema();
include_once 'db.'.$schema->settings->database->type.'.php';

// output buffer setup
function bufferClose($content) { return wrapContent($content); }
//ob_start('bufferClose');
$useDoubleBuffer = false;

$loggedin = isset($_SESSION['user']);
if ($loggedin) $userid = intval($_SESSION['user']);

function application_start($send, $format = 'json') {
	global $schema, $db, $loggedin, $userid;
	$loggedin = false;
//	var_dump($send);
	if (isset($send['cookie'])) {
		$p = $db->getObject('select user, cdate from cookie where cookie = \''.$send['cookie'].'\'');
		if ($p !== null) {
//			var_dump($p);
			if ($p->cdate > time()) {
				$loggedin = true;
				$userid = $p->user;
				$_SESSION['user'] = $userid;
			} else {
				$db->exec('delete from cookie where cookie = \''.$send['cookie'].'\'');
			}
		} else {
			
		}
	}
	// now determine how to render the information and return as response
	$r = '';
	if ($format == 'json') {
		$r = '{'.
			'"title":"'.$schema->settings->application->title.'",'.
			'"backgroundUrl":"'.$schema->settings->application->background.'",'.
			'"theme":"'.$schema->settings->application->theme.'",'.
			'"loggedIn":'.($loggedin?'true':'false').''.
			'}';
	}
	return $r;
}

function user_login($login_object, $format = 'json') {
	global $db, $loggedIn, $userid, $schema;
	$p = $db->getObject('select id, password from user where email = \''.$login_object['email'].'\'');
	$msg = array();
	$cdate = null;
	$cookie = null;
	if ($p !== null) { // user is found, whatabout password?...
		if (md5($login_object['password']) === $p->password) {
			$loggedin = true;
			$userid = $p->id;
			$_SESSION['user'] = $userid;
			$cdate = time() + ($schema->settings->application->{'cookie-days-life'} * (60*60*24));
			$cookie = crypt('cook'.mt_rand().'rice'.mt_rand().'zuo'.mt_rand().'fan'.$userid.mt_rand().$cdate.mt_rand(),crypt($userid.mt_rand().'ren'));
			$db->insert('cookie', array('user', 'cookie', 'cdate'), array($userid, '\''.$cookie.'\'', $cdate), false);
			// this next line is a cleanup for general stray cookies in the table. where else to put it? shouldnt affect startup speed of application, only for logins.
			$db->exec('delete from cookie where cdate < '.time().'');
		} else {
			$msg[] = 'Email or password incorrect.';
		}	
	}	else {
		$msg[] = 'Email or password incorrect.';
	}
	$r = '';
	if ($format == 'json') {
		$r = '{'.
			'"st":"'.($loggedin?'ok':'er').'",'.
			'"msg":'.json_encode($msg).','.
			(is_null($cookie)?'':'"cookie":"'.$cookie.'",').
			(is_null($cookie)?'':'"cdate":'.$cdate.',').
			'"loggedIn":'.($loggedin?'true':'false').''.
			'}';
	}
	return $r;
}

function model_title($modelname) {
	global $schema;
  $r = null;
  // first look explicity defined title
	$m = $schema->model->{$modelname};
	if (isset($m))
		if (isset($m->title))
			$r = $m->title;
	// no title? then make one frmo the camelcased object name
	if (is_null($r)) {
		$r = '';
		for($i = 0; $i < strlen($modelname); $i++) {
			if ($i > 0 && ctype_upper($modelname{$i})) {
				$r .= ' '.$modelname{$i};
			} else {
				$r .= $modelname{$i};
			}
		}
	  $r = ucwords($r);
	}
  return $r;
}

function model_plural_title($modelname) {
  return model_title($modelname) . 's';
}

// routine to check if datastore is prepared to work with the schema
function model_check_entiredb() {
	global $schema, $db;
	foreach (get_object_vars($schema->model) as $m => $model) {
		model_check_datastore($m);
	}
	return '{"st":"ok"}';
}

// routine to check if datastore is prepared to work with specified model defined in the schema
function model_check_datastore($model) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		if (isset($m->persist) && ($m->persist===true)) {
			// first check if hte table exists, if not: create it and add constraints and default data
			if (!$db->hasTable($model)) {
				model_create_datastore($model);
			} else {
				model_verify_datastore_columns($model);
			}
			model_populate_datastore_with_default_data($model);
		}
	}
}

function model_create_datastore($model, $returnSQL = false, $includeData = false) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$r = 'CREATE TABLE '.$model.' (id INTEGER PRIMARY KEY UNIQUE';
		$rt = 'CREATE TABLE '.$model.'_log (id INTEGER PRIMARY KEY UNIQUE, _fk INTEGER';
		$rmm = '';
		$fieldSelect = '';
		$k = get_object_vars($m->field);
		foreach ($k as $key => $value) {
			$normalField = true;

			if ($value->{'type'} == 'rel-many') {
				$normalField = false;
				$mmn = schema_calculate_dynamic_manymany_model_name($model, $key);
				if (!isset($schema->model->{$mmn}))
					schema_generate_dynamic_manymany_model($model, $key);
//				var_dump($mmn);
				if ($returnSQL)
					$rmm .= model_create_datastore($mmn, $returnSQL, $includeData) . "\n";
				else
					model_create_datastore($mmn);
			}
			
			if ($normalField)
				$fieldSelect .= ', ' . $key . ' ' . schema_datatype_to_nativetype($value->type);
		}
		$r .= $fieldSelect . ');'."\n";
		$rt .= $fieldSelect . ', _dateOfChange NUMERIC, _changedBy INTEGER, _deletedBy INTEGER);'."\n";
		if ($returnSQL) {
			$sql = '';
			if ($includeData) // pull data from database and generate backup sql
				foreach($db->getObjects('SELECT * FROM ' . $model) as $record)
					$sql .= model_put_object($model, $record, true)."\n"; // doesn't insert duh, returns the sql that would have.
			return $r.$rt.$sql.$rmm;
		} else {
			$db->exec($r);
			$db->exec($rt);
		}
	}
}

function schema_calculate_dynamic_manymany_model_name($model, $field) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$tn = '';
		$om = isset($m->field->{$field}->model) ? $m->field->{$field}->model : $field;
		if ($model < $om) $tn = $model.'_'.$om; else $tn = $om.'_'.$model;
		return $tn;
	}
	return null;
}

function schema_generate_dynamic_manymany_model($model, $field) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$tn = schema_calculate_dynamic_manymany_model_name($model, $field);
		$schema->model->{$tn} = (object)array(
    	"field" => (object)array(
    		$field => (object)array("type" => "integer", "req" => true ),
    		$model => (object)array("type" => "integer", "req" => true )
			)
		);
//		var_dump($schema->{$tn});
	}
	return null;
}

function schema_generate_dynamic_manymany_data($model, $field) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$om = isset($m->field->{$field}->model) ? $m->field->{$field}->model : $field;
		$view = isset($schema->model->{$om}->view->_checklist) ? '_checklist' : 'default';
		$d = json_decode('{}');
		$d->data = model_view($om, $view, 'internal-raw');
		$d->field = $schema->model->{$om}->view->{$view}->field;
		return $d;
	}
	return null;
}

function schema_generate_lookup_select_data($model, $field) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$om = isset($m->field->{$field}->model) ? $m->field->{$field}->model : $field;
		$view = isset($schema->model->{$om}->view->_lookup) ? '_lookup' : 'default';
		$d = json_decode('{}');
		$d->data = model_view($om, $view, 'internal-raw');
		$d->field = $schema->model->{$om}->view->{$view}->field;
		return $d;
	}
	return null;
}

function model_populate_datastore_with_default_data($model) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		if (isset($m->insert)) {
			foreach ($m->insert as $v => $value) {
				if (count(model_get_objects($model, 
					json_decode('[{"a":"'.$model.'.id", "o":"=", "b":"'.$value->id.'" }]'), 
					array((object)array('source' => $model.'.id'))
				)) == 0) {
					$k = array('id');
					$v = array($value->id);
					foreach (get_object_vars($m->field) as $key => $field) {
						if (isset($value->{$key})) { // from introduction of 'rel-many' field types
							$k[] = $key;
							$v[] = schema_escape_value($value->{$key}, $field);
						}
					}
					$db->insert($model, $k, $v);
				}
			}
		}
	}
}

function model_verify_datastore_columns($model) {  /* INCOMPLETE: TODO. sqlite does not support adding columns */
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		// check schema
		$fi = $db->get('PRAGMA table_info('.$model.');');
		$k = get_object_vars($m->field);
		foreach ($k as $key => $value) {
			$has = false;
			foreach ($fi as $f) 
				if ($f->name == $key)
					$has = true;
			if (!$has) {
//				$db->exec('ALTER TABLE '.$model.' ADD '.$key. ' ' . schema_datatype_to_nativetype($value->type).'');
//				model_create_datastore($model);
			}
		}
	}
}

function model_put_object($model, $value, $returnSQL = false) {
	global $schema, $db, $userid;
	$m = $schema->model->{$model};
	if (isset($m)) {
		$k = array();
		$what = array((object)array('source' => $model.'.id'));
		$v = array();
		$id = null;
		$r = null;
		if (isset($value->id)) $id = $value->id;
		foreach (get_object_vars($m->field) as $key => $field) {
			if (!isset($field->hidden) || $returnSQL) {
				if (isset($value->{$key})) {
					$k[] = $key;
					$what[] = (object)array('source' => $model.'.'.$key);
					$v[] = schema_escape_value($value->{$key}, $field);
				} else {
					if (isset($field->default)) {
						$k[] = $key;
						$what[] = (object)array('source' => $model.'.'.$key);
						$v[] = schema_escape_value($field->default, $field);
					}
				}
			} else {
				if ($id == null) {
					if (isset($field->default)) {
						$k[] = $key;
						$what[] = (object)array('source' => $model.'.'.$key);
						$v[] = schema_escape_value($field->default, $field);
					}
				}
			}
		}
		// now push to the model table
		$sql = '';
		if ($returnSQL) {
			$sql .= $db->push($model, $k, $v, $id, true);
		} else {
			$id = $db->push($model, $k, $v, $id);
			$r = model_get_objects($model, json_decode('[{"a":"id", "o":"=", "b":"'.$id.'" }]'), $what)[0];
		}
		// now insert into history log table
		$k[] = '_fk'; $v[] = ''.$id;
		$k[] = '_dateOfChange'; $v[] = ''.time();
		$k[] = '_ChangedBy'; $v[] = ''.$userid;
		if ($returnSQL) {
			$sql .= "\n".$db->push($model.'_log', $k, $v, null, true);
		} else {
			$db->push($model.'_log', $k, $v, null);
			$r = model_get_objects($model, json_decode('[{"a":"id", "o":"=", "b":"'.$id.'" }]'), $what)[0];
		}
		// now return appropriate response to caller
		if ($returnSQL) {
			return $sql;
		} else {
			return $r;
		}
	}
}

function model_delete_datastore($model) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m) && $db->hasTable($model)) {
		$db->dropTable($model);
	}
}

function model_validate_object($model, $value) {
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		if (isset($m->insert)) {
			$r = '';
			$values = array_values($m->insert);
			foreach ($values as $v)
				print_r($v);
			$r .= '';
			$db->exec($r);
		}
	}
}

/* This function should only be called locally. all foreign or external requests for objects
		should rather be funnelled through model_get_object which has security built in. */
function model_get_objects($model, $filter, $what) {  // '{"filter":[{"field":"name","op":"contains","value":"pat"}]}'
	global $schema, $db;
	$m = $schema->model->{$model};
	if (isset($m)) {
		// what can the user see?
		$fwhat = array();
		$relMany = array();
		
		$hasManySources = false;
		$ffrom = array($model);
		$t_sql = '';
		foreach ($what as $n) {
			if (!property_exists($n, 'source')) {
				var_dump($model);
				var_dump($filter);
				var_dump($what);
				var_dump($n);
			}
			$dotPos = strpos($n->source, '.');
			if ($dotPos !== false) {
				$hasManySources = true;
				$sourceTable = substr($n->source, 0, $dotPos);
				if (in_array($sourceTable, $ffrom) === false)
					$ffrom[] = $sourceTable;
			}
		}
		foreach ($what as $n) {
			if ($n->source != $model.'.id') {
				$dotPos = strpos($n->source, '.');
				$sourceTable = substr($n->source, 0, $dotPos);
				$sourceField = substr($n->source, $dotPos + 1);
				if ($dotPos === false) {
					$sourceTable = $model;
					$sourceField = $n->source;
				}
				$useDisplayValue = false;
				if (property_exists($n, '_useDisplayValue')) $useDisplayValue = $n->_useDisplayValue;

//			echo 'what: ' . $sourceField.'<br />';
				$hidden = false;
				if (isset($schema->model->{$sourceTable}->field->{$sourceField}->hidden)) 
					$hidden = $schema->model->{$sourceTable}->field->{$sourceField}->hidden;
				if (!$hidden) {
					$normalField = true;
					if ($schema->model->{$sourceTable}->field->{$sourceField}->{'type'} == 'rel-many') {
						$relMany[$sourceField] = $schema->model->{$sourceTable}->field->{$sourceField};
						$normalField = false;
					}
					if ($normalField) {
						if ($useDisplayValue)
							$fwhat[] = 'CASE '.$sourceTable.'.'.$sourceField . ' WHEN 0 THEN \''.$schema->model->{$sourceTable}->field->{$sourceField}->options->n.'\' ELSE \''.$schema->model->{$sourceTable}->field->{$sourceField}->options->y.'\' END as \'' . (property_exists($n, 'as') ? $n->as : $sourceField) . '\'';
						else 
							$fwhat[] = $sourceTable.'.'.$sourceField . ' as \'' . (property_exists($n, 'as') ? $n->as : $sourceField) . '\'';
					}
				}
			} else {
				$fwhat[] = $model . '.id as id';
			}
		}
		// field select sql
		$fs = ''; foreach ($fwhat as $d) { $fs .= ($fs == '' ? '' : ', ') . $d; }
		// from table sql
		$ff = ''; foreach ($ffrom as $d) { $ff .= ($ff == '' ? '' : ', ') . $d; }
		// build filters
		$ws = '(1=1)';
		if (isset($filter))
			foreach ($filter as $f) {
				$ws .= ' AND ' . build_sql_filter($f->a, isset($f->b) ? $f->b : null, $f->o, $model);
			}
		$t_sql = 'select '.$fs.' from '.$ff.' where '.$ws;

//if ($model != 'role')		echo $t_sql.'<br />';

		// return object
		$te = $db->getObjects($t_sql);
		if (count($relMany) > 0) { // add contents of many-many relationships
			foreach($relMany as $relName=>$relData) {
				// get mana many table name
				$mmn = schema_calculate_dynamic_manymany_model_name($model, $relName);
				// if the schema was not specified in file, then auto generate it
				if (!isset($schema->model->{$mmn})) schema_generate_dynamic_manymany_model($model, $relName);
				$om = isset($m->field->{$relName}->model) ? $m->field->{$relName}->model : $relName;
				$om = (object)array('source' => $mmn.'.'.$om, 'as' => $om);
				// now populate response with data from respective tables	
				foreach($te as $record) {
					$record->{$relName} = model_get_objects($mmn, array(json_decode('{"a":"'.$mmn.'.'.$model.'", "o":"=", "b":"'.$record->id.'" }')), array($om));
// TODO: include filter from _checklist/_lookup view of lookup table... this will prevent inactive data from being transmitted to the client (use case: add role to user, deactivate role, user still has reference to role, view user in client, inactive roles will still be trasnmitted to client)
				}
			}
		}
		return $te;
	}
}

function build_sql_filter_fully_qualify_attr($a, $b, $model) {
	global $schema;
	if (substr($b, 0, 1) === '\'') { 
		return $b;
	} else {
		$dotPos = strpos($b, '.');
		if ($dotPos !== false)	{ // field b is a database field, return the field name
			return $b;
		} else { // b is not a db field, return the hard coded value escaped by the proper db class wrapper (db.sqlite.php)
			$dotPos = strpos($a, '.');
			$sourceTable = substr($a, 0, $dotPos);
			$sourceField = substr($a, $dotPos + 1);
			if ($dotPos === false) {
				$sourceTable = $model;
				$sourceField = $a;
			}
			if (property_exists($schema->model, $sourceTable)) {
				if (property_exists($schema->model->{$sourceTable}->field, $sourceField))
					return schema_escape_value($b, $schema->model->{$sourceTable}->field->{$sourceField});
				if ($sourceField == 'id')
					return $b;
			}
			echo 'Error fetching '. $sourceTable .' . ' . $sourceField.'<br />';
		}
	}
}

function build_sql_filter($a, $b, $c, $model) {
	global $schema;
	$r = '';
	if (gettype($a) == 'string') {
		$r = '('.database_sql_operator($a, $c, build_sql_filter_fully_qualify_attr($a, $b, $model)).')';
	}
	if (gettype($a) == 'array') {
		foreach ($a as $e)
			$r .= ($r == '' ? '' : ' '.$c.' ') . build_sql_filter($e->a, isset($e->b) ? $e->b : null, $e->o, $model);
		$r = '(' . $r . ')';
	}
	return $r;
}


function model_view($model, $view = 'default', $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$s = null;
	if (gettype($view) == 'string') {
		$s = $m->view->{$view};
	} else {
		$s = $view;
	}

	if (isset($m) && isset($s)) {
		// determine what fields/columns the user can see
		$what = array((object)array('source' => $model.'.id'));
		$fieldNames = array();
		$linkFilters = array();
		foreach ($s->field as $field) {
			$dotPos = strpos($field->source, '.');
			$sourceTable = substr($field->source, 0, $dotPos);
			$sourceField = substr($field->source, $dotPos + 1);
			if ($dotPos === false) {
				$sourceTable = $model;
				$sourceField = $field->source;
			}
			
			// calculate name of field. ternary operators allow for shorthand in schema
			if (!property_exists($field, 'as')) $field->as = ($model == $sourceTable ? $sourceField : $sourceTable);
			$what[] = $field;
			$fieldNames[] = (object)array('source' => (property_exists($field, 'as') ? 
				$field->as : ($model == $sourceTable ? $sourceField : $sourceTable)
			));
			
			// the following adds a link-filter between master and lookup tables
			if ($model != $sourceTable)
				$linkFilters[] = (object)array('a'=>$model.'.'.$sourceTable, 'o'=>'=', 'b'=>$sourceTable.'.id' );
				
		}
		
		// determine what records the user can view
		$filter = array();
		if (property_exists($s, 'filter')) $filter = $s->filter;
		foreach ($linkFilters as $lf) $filter[] = $lf; // this adds filters for links between tables
		$data = model_get_objects($model, $filter, $what);

		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{'.
				'"title":"'.(isset($s->title) ? $s->title : model_plural_title($model)).'",'.
				'"field":'.json_encode($fieldNames).','.
				'"data":'.json_encode($data).''.
				'}';
		}
		if ($format == 'internal-raw') {
			$r = $data;
		}
		return $r;
	}
}

function model_edit($model, $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$v = $m->view->default;

	if (isset($m) && isset($v)) {
		// determine what fields/columns the user can see
		$fschema = array();
		foreach ($m->field as $n=>$field) {
			$hidden = false;
			if (isset($field->hidden)) $hidden = $field->hidden;
			if (!$hidden) {
				$field->name = $n;
				if ($field->type == 'rel-many') {
					$field->options = schema_generate_dynamic_manymany_data($model, $n);
				}

				if (isset($field->control) && ($field->control == 'select')) {
					$field->options = schema_generate_lookup_select_data($model, $n);
				}

				$fschema[] = $field;
			}
		}

		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{'.
				'"title":"'.(isset($v->title) ? $v->title : model_plural_title($model)).'",'.
				'"canSave":true,'.
				'"field":'.json_encode($fschema).''.
				'}';
		}
		return $r;
	}
}

function model_get($model, $value = null, $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$v = $m->view->default;

	if (isset($m) && isset($v)) {
	
		$what = array((object)array('source' => $model.'.id'));
		foreach ($m->field as $n=>$field) {
			$what[] = (object)array(
				'source' => $model.'.'.$n,
				'as' => $n
			);
		}

		// determine what record the user wants to view
		$filter = array();
		if (property_exists($v, 'filter')) $filter = $v->filter;
		$data = null;
		if (isset($value)) {
			if (strToUpper($value) !== 'NEW') {
				$id = $value;
				$filter[] = json_decode('{"a":"'.$model.'.id", "o":"=", "b":"'.$value.'" }');
//			echo '{"a":".id", "o":"=", "b":"'.$value.'" }';
//var_dump($what);
//var_dump($model);
//var_dump($m);
				$data = model_get_objects($model, $filter, $what);
			}
		}
		if ($data === false) $data = array();
		if (count($data) > 0) {
			$data = reset($data);
			// want to properly type the data before it is json to client
			
		} else {
			
		}
		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') $r = '{"data":'.json_encode($data).'}';
		if ($format == 'internal-raw') return $data;
		return $r;
	}
}

// unused
function model_default($model, $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$v = $m->view->default;

	if (isset($m) && isset($v)) {
		// determine what fields/columns the user can see
		$f = array();
		foreach ($m->field as $n=>$field) {
			$hidden = false;
			if (isset($field->hidden)) $hidden = $field->hidden;
			if (!$hidden) {
				$f[$n] = isset($field->default) ? $field->default : null;
			}
		}
		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{"default":'.json_encode($f).'}';
		}
		if ($format == 'internal-raw') return $f;
		return $r;
	}
}

function model_set($model, $value, $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$v = $m->view->default;
	if (gettype($value) == 'array') $value = (object)$value;

	if (isset($m) && isset($v)) {
		// determine what fields/columns the user can see (ie: set)
		$what = array((object)array('source' => $model.'.id', 'as'=>'id'));
		$relMany = array();
		foreach ($m->field as $n=>$field) {

			$hidden = false;
			if (isset($field->hidden)) $hidden = $field->hidden;
			if (!$hidden) {
				$normalField = true;

				if ($field->type == 'rel-many') {
					$relMany[$n] = $field;
					$normalField = false;
				}
				
				if ($normalField)
					$what[] = (object)array(
						'source' => $model.'.'.$n,
						'as' => $n
					);
				}
		}
		$tr = 'ok';
		$tm = array();
		$rec = array();
		foreach ($what as $wf) {
			// do check for new field value validates against rules in schema.js
			if (property_exists($value, $wf->as))
				$rec[$wf->as] = $value->{$wf->as};
		}
		if ($tr == 'ok') { // no error from the field validations, can push to db
//		var_dump(!isset($value->id) || $value->id == null || intval($value->id) < 1);
			$success = false;
			$ssid = 0;
			if (!isset($value->id) || ($value->id == null) || (strToUpper(''.$value->id) == 'NEW') || (intval($value->id) < 1)) { // insert new data
				$rec = (object)$rec;
				$ssid = model_put_object($model, $rec)->id;
				$success = true;
			} else { // update existing record
				$rec['id'] = intval($value->id);
				$rec = (object)$rec;
				// determine what records the user can view (if they cant view it, then they probably shouldnt update it)
				$filter = array();
				if (property_exists($v, 'filter')) $filter = $v->filter;
				$filter[] = json_decode('{"a":"'.$model.'.id", "o":"=", "b":"'.intval($value->id).'" }');
				$data = model_get_objects($model, $filter, $what);
				if (count($data) == 0) { // not allowed to update
					$tr = 'er';
					$tm[] = 'Not allowed to update this record.';
				} else { // can update ==> update fields and push to database
					$ssid = model_put_object($model, $rec)->id;
					$success = true;
				}
			}
			if (($success == true) && (count($relMany) > 0)) { // add contents of many-many relationships
				foreach($relMany as $relName=>$relData) {
					// get mana many table name
					$mmn = schema_calculate_dynamic_manymany_model_name($model, $relName);
					// if the schema was not specified in file, then auto generate it
					if (!isset($schema->model->{$mmn})) schema_generate_dynamic_manymany_model($model, $relName);
					$om = isset($m->field->{$relName}->model) ? $m->field->{$relName}->model : $relName;
					
					$indbe = model_get_objects($mmn, array(json_decode('{"a":"'.$mmn.'.'.$model.'", "o":"=", "b":"'.$ssid.'" }')), 
						array((object)array('source'=>$om))
					);
					$indb = array();
					foreach($indbe as $ele) { $indb[] = $ele->{$om}; }
					
					$inrc = array();
					if (property_exists($value, $relName)) {
						$inrb = $value->{$relName};
						foreach($inrb as $ele) { $inrc[] = $ele[$om]; }
					}
					
					$toDelete = array_diff($indb, $inrc);
					$toAdd = array_diff($inrc, $indb);
					$toUpdate = array_intersect($indb, $inrc); // just here for future reference, havent implemented updating yet.

					// delete unwanted relationships
					$idl = '';
					foreach($toDelete as $e) { $idl .= ($idl == '' ? '' : ',') . $e; }
					if (count($toDelete) > 0)
						$db->execute('DELETE FROM '.$mmn.' WHERE ('.$om.' in ('.$idl.')) AND ('.$model.' = '.$ssid.')');
					
					// insert new relationships
					if (count($toAdd) > 0) {
						$idl = '';
						foreach($toAdd as $e) { 
							$idl .= 'INSERT INTO '.$mmn.'('.$om.', '.$model.') VALUES ('.$e.', '.$ssid.'); ';
						}
//					var_dump($idl);
						$db->execute($idl);
					}
					// no need to update yet, not until we have extra values in relationship table.
					
				}
			}
		}
		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{"st":"'.$tr.'", "msg":'.json_encode($tm).'}';
		}
		return $r;
	}
}

function model_search($model, $view, $query, $format = 'json') {
	global $schema, $db;
	$m = $schema->model->{$model};
	$s = null;
	if (gettype($view) == 'string') {
		$s = $m->view->{$view};
	} else {
		$s = $view;
	}

	if (isset($m) && isset($s)) {
		// determine what fields/columns the user can see
		$what = array((object)array('source' => $model.'.id'));
		$fieldNames = array();
		$linkFilters = array();
		foreach ($s->field as $field) {
			$dotPos = strpos($field->source, '.');
			$sourceTable = substr($field->source, 0, $dotPos);
			$sourceField = substr($field->source, $dotPos + 1);
			if ($dotPos === false) {
				$sourceTable = $model;
				$sourceField = $field->source;
			}
			
			// calculate name of field. ternary operators allow for shorthand in schema
			if (!property_exists($field, 'as')) $field->as = ($model == $sourceTable ? $sourceField : $sourceTable);
			$what[] = $field;
			$fieldNames[] = (object)array('source' => (property_exists($field, 'as') ? 
				$field->as : ($model == $sourceTable ? $sourceField : $sourceTable)
			));
			
			// the following adds a link-filter between master and lookup tables
			if ($model != $sourceTable)
				$linkFilters[] = (object)array('a'=>$model.'.'.$sourceTable, 'o'=>'=', 'b'=>$sourceTable.'.id' );
				
		}
		
		// determine what records the user can view
		$filter = $s->filter;
		foreach ($linkFilters as $lf) $filter[] = $lf; // this adds filters for links between tables

		// add the search query in (search every being returned field)
		$vf = '{"a": '.json_encode($filter).', "o": "and"}';
		$qf = '';
		foreach ($what as $w)
			if ($w->source != $model.'.id') {
				$dotPos = strpos($w->source, '.');
				$sourceTable = substr($w->source, 0, $dotPos);
				$sourceField = substr($w->source, $dotPos + 1);
				if ($dotPos === false) {
					$sourceTable = $model;
					$sourceField = $w->source;
				}
				if ($schema->model->{$sourceTable}->field->{$sourceField}->type != 'string') continue;
				$qf .= ($qf == '' ? '' : ',').'{"a": "'.$sourceTable.'.'.$sourceField.'", "o": "contains", "b":"'.$query.'"}';
			}
		$qf = '{"a": ['.$qf.'], "o": "or"}';
		$data = model_get_objects($model, json_decode('['.$qf.', '.$vf.']'), $what);

		// query objects from db
//		$data = model_get_objects($model, $filter, $what);

		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{'.
				'"title":"'.(isset($s->title) ? $s->title : model_plural_title($model)).'",'.
				'"field":'.json_encode($fieldNames).','.
				'"data":'.json_encode($data).''.
				'}';
		}
		if ($format == 'internal-raw') {
			$r = $data;
		}
		return $r;
	}
}

function model_overview($model, $format = 'json') {
	global $schema, $db;
//	var_dump($model);
	$m = $schema->model->{$model};
	if (isset($m)) {
		// determine what views the user can see
		$w = '';
		foreach (get_object_vars($m->view) as $n => $v)
			if (substr($n, 0, 1) != '_')
				$w .= ($w == '' ? '' : ',').'{"name":"'.$n.'","title":"'.(isset($v->title) ? $v->title : 'View All Records').'"}';

		// determine what actions are available
		$a = '';
		if (isset($m->action))
			foreach (get_object_vars($m->action) as $n => $v)
				if ($v->per == 'class')
					$a .= ($a == '' ? '' : ',').'{"name":"'.$n.'","title":"'.(isset($v->title) ? $v->title : $n).'"}';

		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			$r = '{'.
				'"title":"'.model_plural_title($model).'",'.
				'"view":['.$w.'],'.
				'"action":['.$a.'],'.
				'"canCreate":true,'.
				'"canExport":true'.
				'}';
		}
		return $r;
	}
}
	function model_action($model, $action, $format = 'json') {
	global $schema, $db, $userid;
	$m = $schema->model->{$model};
	$result = null;
	if (isset($m)) {
		$a = '';
		if (isset($m->action)) {
			if (isset($m->action->{$action})) {
				$act_handle = 'action_'.$action;
				$result = $act_handle($model, $userid);
			}
		}
		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'json') {
			if (isset($m->action->{$action}->title)) $r .= '"title":"'.$m->action->{$action}->title.'"';
			if (isset($m->action->{$action}->css)) $r .= ($r == '' ? '' : ',') . '"css":"'.$m->action->{$action}->css.'"';
			$r .= ($r == '' ? '' : ',') . '"result":"'.str_replace('"', '\"', $result).'"';
			$r = '{' . $r . '}';
		}
		return $r;
	}
}
	
function model_ping($format = 'json') {
	$r = '';
	if ($format == 'json') {
		$r = '{'.
			'"st":"ok",'.
			'"cache":false'.
			'}';
	}
	return $r;
}
	
function menu_view($menu = 'default', $format = 'json') {
	global $schema, $db;
	$m = $schema->menu->{$menu};
	if (isset($m)) {
		// determine what options the user can see
		$op = array();
		foreach($m->option as $o) {
//		var_dump($o);
			if ($o->type == 'view') {
				$tm = $schema->model->{$o->model};
				$tv = (isset($o->view) ? $tm->view->{$o->view} : $tm->view->default);
				if (!isset($o->title)) $o->title = (isset($tv->title) ? $tv->title : model_plural_title(isset($tm->title) ? $tm->title : $o->model) );
			}
			if ($o->type == 'menu') {
				$tm = $schema->menu->{$o->source};
				if (!isset($o->title)) $o->title = (isset($tm->title) ? $tm->title : model_title($o->source));
			}
			$op[] = $o;
		}
		
		// now determine how to render the information and return as response
		$r = '';
		if ($format == 'mhtml') {
			$r = 'Not coded yet :(';
		}

		if ($format == 'json') {
			$r = '{'.
				'"title":"'.(isset($m->title) ? $m->title : $schema->settings->applicationTitle).'",'.
				'"option":'.json_encode($op).''.
				'}';
		}
		return $r;
	}
}
	
	
function wrapContent($content) {
	global $loggedin, $useDoubleBuffer;
/*	if ($useDoubleBuffer) {
		$s = '<!DOCTYPE html><html></html>';
		return $s;
	} else {*/
		return $content;
	//}
}

function database_backup($user) {
	global $schema, $db;
	$sql = '';
	foreach (get_object_vars($schema->model) as $m => $model) {
		if (isset($model->persist) && ($model->persist===true))
			$sql .= model_create_datastore($m, true, true)."\n";
	}
	$dateStamp = '2013-06-10T12:01';
	$filename = str_replace('-', '', str_replace(':', '', str_replace('T', '', $dateStamp))) . '.txt';
	// write to file
	file_put_contents('backups/'.$filename, $sql);
	
	// write record to db
	model_set('backup', (object)array(
		'datetime' => $dateStamp, 
		'filename' => $filename,
		'user' => $user
		)
	);
}

?>