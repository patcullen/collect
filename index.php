<!DOCTYPE html>
<html><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
	<link rel="stylesheet" href="static/jquery.mobile-1.2.0.min.css" />
	<script type="text/javascript" src="static/jquery.min.js"></script>
	<script type="text/javascript" src="static/jquery.mobile-1.2.0.js"></script>

	<script type="text/javascript" src="static/jquery.tmpl.min.js"></script>
	<script>jQuery.support.localStorage = true;</script>
	<script type="text/javascript" src="static/jquery.offline.js"></script>

	<script type="text/javascript" src="app/custom.js"></script>
	<link rel="stylesheet" href="app/custom.css" />

<!-- Page templates -->
	
<script id="searchModelTemplate" type="text/x-jquery-tmpl">
	<label for="elListSearch" class="ui-hidden-accessible">Search Input:</label>
	<input type="search" name="search" id="elListSearch" value="" />
	<div class="searchResult"></div>
</script>

<script id="listTemplate" type="text/x-jquery-tmpl">
	<ul data-role="listview" data-divider-theme="{{if data.length == 0}}e{{else}}b{{/if}}" data-inset="false">
		{{if data.length == 0}}
			<li data-role="list-divider" role="heading">No Records</li>
		{{/if}}
		{{each(j,o) data}}
			<li data-theme="c"><a href="api/page.php?mode=edit&model=${model}&value=${o.id}" data-transition="slide">
				{{each(i) field}} {{if i==0}}<h3 class="${source}">${o[source]}</h3>{{else}}<p class="${source}">${o[source]}</p>{{/if}} {{/each}}
			</a></li>
		{{/each}}
	</ul>
</script>

<script id="menuTemplate" type="text/x-jquery-tmpl">
	<ul data-role="listview" data-divider-theme="b" data-inset="false">
		{{each(i, o) option}}
			{{if o.type=='divider'}}
				{{if o.title == ''}}
					<li data-role="list-divider" role="heading">&nbsp;</li>
				{{else}}
					<li data-role="list-divider" role="heading">${o.title}</li>
				{{/if}}
			{{/if}}
			{{if o.type=='edit'}}
				<li data-theme="c"><a href="api/page.php?mode=edit&model=${o.model}&value=${o.value}" data-transition="slide">${o.title}</a></li>
			{{/if}}
			{{if o.type=='view'}}
				<li data-theme="c"><a href="api/page.php?mode=view&model=${o.model}&source=${o.view}" data-transition="slide">${o.title}</a></li>
			{{/if}}
			{{if o.type=='ovvw'}}
				<li data-theme="c"><a href="api/page.php?mode=ovvw&model=${o.model}" data-transition="slide">${o.title}</a></li>
			{{/if}}
			{{if o.type=='menu'}}
				<li data-theme="c"><a href="api/page.php?mode=menu&model=${o.source}" data-transition="slide">${o.title}</a></li>
			{{/if}}
			{{if o.type=='cstm'}}
				<li data-theme="c"><a href="api/page.php?mode=cstm&model=${o.model}&title=${o.title}" data-transition="slide">${o.title}</a></li>
			{{/if}}
		{{/each}}
	</ul>
</script>

<script id="overviewTemplate" type="text/x-jquery-tmpl">
	<ul data-role="listview" data-divider-theme="b" data-inset="false">
		<li data-role="list-divider" role="heading">Quick Find</li>
		<li data-theme="c"><a href="api/page.php?mode=srch&model=${model}&source=default" data-transition="slide">Search</a></li>
		<li data-role="list-divider" role="heading">View</li>
		{{if view.length > 0 }}
			{{each(i, o) view}}
				<li data-theme="c"><a href="api/page.php?mode=view&model=${model}&source=${o.name}" data-transition="slide">${o.title}</a></li>
			{{/each}}
		{{/if}}
		<!--{{if canExport || action}}
			{{if canExport }}
				<li data-role="list-divider" role="heading">Other</li>
			{{/if}}
			{{if action}}
				<li data-theme="c"><a href="api/page.php?mode=expt&model=${model}" data-transition="slide">Export &amp; Download</a></li>
				{{if action.length > 0 }}
					{{each(i, o) action}}
						<li data-theme="c"><a href="api/page.php?mode=actn&model=${model}&value=${o.name}" data-transition="slide">${o.title}</a></li>
					{{/each}}
				{{/if}}
			{{/if}}
		{{/if}}-->
	</ul>
</script>

<script id="validationErrorTemplate" type="text/x-jquery-tmpl">
	<div data-role="collapsible" data-collapsed="false" data-theme="e"><h2>Please ammend:</h2>
	<ul data-role="listview"> <!--  data-divider-theme="e" data-inset="true" -->
		<!--<li data-role="list-divider" role="heading">Please ammend the following:</li>-->
		{{each(i, o) msg}}
			<li data-icon="delete">${o}</li>
		{{/each}}
	</ul></div>
</script>

<script id="successMessageTemplate" type="text/x-jquery-tmpl">
	<div data-role="collapsible" data-inset="false" data-expanded-icon="check" data-collapsed="false" data-theme="b"><h2>{{if msg}}${msg}{{else}}Success{{/if}}</h2>
	</div>
</script>

<script id="editTemplate" type="text/x-jquery-tmpl">
	<div data-role="content" class="content-primary editScreen">
	<div class="validationErrors"></div>
	<div class="successMessage"></div>
	<form action="" method="POST" id="fEditForm"><!--<ul data-role="listview">-->
	{{each(i, f) field}}
		<div data-role="fieldcontain"><fieldset data-role="controlgroup">
		{{if type=='string'}}
			<label for="${f.name}">${pat.fromCamelToLabel(f.name)}</label>
			{{if regex=='password'}}
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="password" />
			{{else regex=='email'}}
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="email" />
			{{else}}
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="text" />
			{{/if}}
		{{/if}}
		{{if type=='date'}}
			<label for="${f.name}">${pat.fromCamelToLabel(f.name)}</label>
			{{if regex=='date'}}
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="date" />
			{{else regex=='datetime'}}
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="datetime-local" />
			{{/if}}
		{{/if}}
		{{if type=='boolean'}}
			<label for="${f.name}" class="ui-hidden-accessible">${pat.fromCamelToLabel(f.name)}</label>
			<select name="${f.name}" id="f${f.name}" data-role="slider">
				<option value="off">${f.options?f.options.n:'No'}</option><option value="on">${f.options?f.options.y:'Yes'}</option>
			</select>
		{{/if}}
		{{if type=='integer'}}
			{{if control=='select'}}
				<label for="${f.name}">${pat.fromCamelToLabel(f.name)}</label>
				<select name="${f.name}" id="f${f.name}">
				{{each(i, d) options.data}}
				  <option value="${d.id}">${d[options.field[0].source]}</option>
				{{/each}}
				</select>
			{{/if}}
			{{if control=='input'}}
				<label for="${f.name}">${pat.fromCamelToLabel(f.name)}</label>
				<input name="${f.name}" id="f${f.name}" {{if f.req}}placeholder="Required"{{/if}} value="" type="text" />
			{{/if}}
		{{/if}}
		{{if type=='rel-many'}}
			{{if control=='checklist'}}
			  <legend>${f.description}</legend>
				{{each(i, d) options.data}}
				  <input type="checkbox" name="${f.name}_${d.id}" id="f${f.name}_${d.id}" /><label for="f${f.name}_${d.id}">${d[options.field[0].source]}</label>
				{{/each}}
			{{/if}}
		{{/if}}
		</fieldset></div>
	{{/each}}
	<!--</ul>--></form></div>
</script>

<script id="debugTemplate" type="text/x-jquery-tmpl">
	{{each(i, s) sec}}
		<div data-role="collapsible" data-collapsed="true"><h2>${s.name}</h2><table class="debug">
		{{each(i, p) prop}}
			<tr><th{{if i%2==0}} class="other"{{/if}}>${p.k}</th><td{{if i%2==0}} class="other"{{/if}}>${p.v}</td>
		{{/each}}
		</table>{{if command}}
		{{each(i, p) command}}
			<div><a href="javascript:${p.v}">${p.k}</a></div>
		{{/each}}
		{{/if}}
	</div>
	{{/each}}
</script>

<script id="cstmTemplate" type="text/x-jquery-tmpl">
	<table class="debug custom">
		{{if method}}<tr><th>Method</th><td>${method}</td>{{/if}}
		{{if st}}<tr><th>Status</th><td>${st}</td>{{/if}}
		{{if data}}<tr><th>data</th><td>${JSON.encode(data)}</td>{{/if}}
		{{if msg}}<tr><th>Messages</th><td>${JSON.encode(msg)}</td>{{/if}}
	</table>
	{{if result}}<div id="result" class="custom ${css}">${result}</div>{{/if}}
</script>

<script id="loginTemplate" type="text/x-jquery-tmpl">
	<div class="loginScreen">
		<div class="validationErrors"></div>
		<div data-role="fieldcontain">
			<fieldset data-role="controlgroup">
				<label for="email"></label>
				<input name="email" id="pfEmail" placeholder="Email" value="" type="email">
			</fieldset>
		</div>
		<div data-role="fieldcontain">
			<fieldset data-role="controlgroup">
				<label for="password"></label>
				<input name="password" id="pfPassword" placeholder="Password" value="" type="password">
			</fieldset>
		</div>
		<a data-role="button" data-theme="b" href="javascript:pat.login()">Sign In</a>
		<!--<div><a id="forgot" href="javascript:pat.forgotPassword()">Forgot Password</a></div>-->
	</div>
</script>

<style>

.ui-slider {
	width: 40% !important;
	margin: 0 3% 0 3% !important;
	min-width: 80px !important;
	max-width: 180px !important;
}
table.debug { font-family: monospace; font-size: 11px; color: #000/*DEDA6A*/; }
table.debug th, table.debug td { min-width: 100px; max-width: 700px; text-align: left; vertical-align: top; }
table.debug th { max-width: 250px }
table.debug .other { color: #444/*839FEB*/; }
table.debug th, table.debug td {
	white-space: -moz-pre-wrap;  /* Firefox */
	white-space: -pre-wrap;      /* Older Opera 4 - 6 */
	white-space: -o-pre-wrap;    /* Opera 7+ */
	word-wrap: break-word;       /* IE 5.5+ and Safari */
	white-space: pre-wrap;       /* css-3 problematic browser support */
}

/* The following two lines because we will use a custom background for the application */
/*.ui-page { background: transparent; }
.ui-content{ background: transparent; }*/

</style>
<script>
var pat = {schema: {
	"login": {
		"email": { "type": "string", "req": true, "regex": "email" },
		"password": { "type": "string", "req": true, "min": 6, "max": 99 }
	}
}};

pat.createPage = function(options) {
	/* options :
			id, title, template, data (options for specific generators)
			topButton, excludeBack, dontAttach
	*/
	var id = options.id ? options.id : '';
	var title = options.title ? options.title : '';
	var p = '<div data-role="'+(options.dialog?'dialog':'page')+'" id="'+id+'">';
	p += '<div data-theme="'+pat.local.get('valve.theme', 'b')+'" data-role="header" data-position="fixed">' + 
		'<h3 class="heading">'+title+'</h3>';
//	if (!options.dialog) {
	if (options.topButton) p += '<a data-role="button" data-theme="b" href="javascript:pat.topButton();" class="ui-btn-right">'+options.topButton.title+'</a>';
	if (!options.excludeBack) p += '<a data-role="button" data-direction="reverse" data-icon="arrow-l" data-iconpos="left" class="ui-btn-left" data-rel="back">Back</a>';
//	}
	p += '</div>';
//	p += '<div data-role="content" class="content"></div></div>';
	p = $(p);
//	if (options.dialog) { console.log('ddddialog'); p.dialog(); }
	p.first('.content').append($('#'+options.template).tmpl(options.data));
//	console.log(p, options.data);
	if (!options.dontAttach) {
		if (id != '') $('#' + id).remove();
		$('#body').append(p);
	}
	return p;
};

pat.createViewPage = function(options, callback) {
	/* options : 
			*model, source, filter, noCache, title 
			*id, dontAttach, topButton, excludeBack
	*/
	var jsonops = { model: options.model, mode: 'view' };
	if (options.source) jsonops.source = options.source;
	if (options.filter) jsonops.filter = options.filter;
	$.retrieveJSON('api/page.php', { data: jsonops }, function(json, status, opt) {
		if (status == 'success') {
			json.model = options.model;
			var p = pat.createPage({ 
				id: options.id, title: options.title ? options.title : json.title, template: 'listTemplate', data: json,
				dontAttach: options.dontAttach, includeSave: options.topButton, excludeBack: options.excludeBack
			});
			if (callback) callback(p);
		}
	});	
};

pat.createSrchPage = function(options, callback) {
	var page = pat.createPage({ 
		id: options.id, title: options.title ? options.title : '', template: 'searchModelTemplate', data: {},
		dontAttach: options.dontAttach, topButton: { title: 'Search' }, excludeBack: options.excludeBack
	});
	var currentSearchValue = '';
	var jsonops = { model: options.model, mode: 'srch' };
	if (options.source) jsonops.source = options.source;
	var doSearch = function() {
		jsonops.value = currentSearchValue;
		$.retrieveJSON('api/page.php', { data: jsonops }, function(json, status, opt) {
			if (status == 'success') {
//				console.log(json);
				var t = page.find('div.searchResult').first();
//				console.log(page.find('div.searchResult'));
				t.empty();
				json.model = options.model;
				var lv = $('#listTemplate').tmpl(json);
				t.append(lv);
				lv.listview().listview('refresh');
//				console.log('refreshed...');
			}
		});
	};
	pat.setTopButtonHandler(doSearch);
	$("div.ui-input-search").live("keypress", function(e){
		if (e.target.id == 'elListSearch') {
			if (e.charCode == 13) {
				currentSearchValue = $(e.srcElement).val();
				//doSearch();
				pat.topButton();
				//$(e.srcElement).hide().show();
			} else {
//				console.log('kc',String.fromCharCode(e.charCode), e);
				currentSearchValue = $(e.srcElement).val();
			}
		}
	});
	if (callback) callback(page);
};

pat.displayErrorMessages = function(errorObject, container) {
	if (errorObject == null) {
		var pc = $('.'+container+' div.validationErrors').first();
//		console.log(pc);
		if (pc) pc.empty();
	} else {
		var pc = $('.'+container+' div.validationErrors');
		pc.empty();
		var lv = $('#validationErrorTemplate').tmpl(errorObject).first();
		pc.append(lv);
		lv.collapsible();
		lv.find('ul').listview().listview('refresh');
	}
};

pat.displaySuccessMessage = function(errorObject, container) {
	if (errorObject == null) {
		var pc = $('.'+container+' div.successMessage').first();
		if (pc) pc.empty();
	} else {
		var pc = $('.'+container+' div.successMessage');
		pc.empty();
		var lv = $('#successMessageTemplate').tmpl(errorObject).first();
		pc.append(lv);
		lv.collapsible();
		lv.find('ul').listview().listview('refresh');
	}
};

pat.createEditPage = function(options, callback) {
	var field = null, oid = null, p = null;
	var setData = function(data) {
		oid = data.id;
		$(field).each(function(i, f) {
			if (f.type == 'rel-many') {
				$(data[f.name]).each(function(j, de) {
					var t = $('#'+options.id + ' #f'+f.name+'_'+de[f.name]);
					t.attr("checked",true);//.checkboxradio("refresh");
				});
			} else {
				var t = $('#'+options.id + ' #f'+f.name);
//				console.log(t);
				// generally just try set the value
				t.val(data[f.name]);
				// exception cases below: (like on/off contrls are finicky)
				if (f.type == 'boolean') t.val(''+data[f.name] == '1' ? 'on' : 'off');
			}
		});
	};
	var getData = function() {
		var sto = { id: oid };
		$(field).each(function(i, f) {
			var setFlag = false;
			if (f.type == 'rel-many') {
				sto[f.name] = [];
				$(f.options.data).each(function(j, de) {
					if ($('#'+options.id + ' #f'+f.name+'_'+de.id).attr('checked') == 'checked') {
						var t = {}; t[f.name] = de.id;
						sto[f.name].push(t);
					}
				});
			  setFlag = true;
			}
			if (!setFlag) {
				var t = $('#'+options.id + ' #f'+f.name);
				// generally just try get the value
				sto[f.name] = t.val();
				// exception cases and data-typing below: (like on/off contrls are finicky)
				if (f.type == 'boolean') sto[f.name] = (t.val() == 'on' ? true : false);
				if (f.type == 'integer') sto[f.name] = parseInt(sto[f.name]);
			}
		});
		return sto;
	};
	var doSave = function() {
		var t = getData(), schema = {};
		$(field).each(function(i, f) { schema[f.name] = f; });
		console.log(t);
		//console.log(pat.vjs(t, schema));
		var v = pat.vjs(t, schema);
		if (v.st != 'ok') {
			pat.displayErrorMessages(v, 'editScreen');
		} else {
			pat.displayErrorMessages(null, 'editScreen');
			$(field).each(function(i, f) { var tt = $('#f'+f.name);
				if (f.type == 'rel-many') try { $(f.options.data).each(function(j, de) { $('#'+options.id + ' #f'+f.name+'_'+de.id).checkboxradio('disable'); });	return;} catch (err) {}
				try { tt.slider('disable'); return;} catch (err) {}
				try { tt.selectmenu('disable'); return;} catch (err) {}
				try { tt.textinput('disable'); return;} catch (err) {}
			});
			$.mobile.loading('show',{textVisible: true, text: "Saving" });
			console.log('object sent to server: ', t);
			$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'set', value: t } }, function(data, status, opt) {
				if (status == 'success') {
					$.mobile.loading('hide');
					if (data.st == 'ok') { // data saved, move out of form and back in history
						pat.displaySuccessMessage({msg:null}, 'editScreen');
						setTimeout(function() {
							window.history.go(-1);
						}, 2000);
						//$.mobile.changePage('history.back');
					} else { // could not save, display errors
						pat.displayErrorMessages(data);
					}
				}
			});
			
		}
	};
//console.log('to server for page', { mode: 'edit', model: options.model }, options);
	$.retrieveJSON('api/page.php', { data: { mode: 'edit', model: options.model } }, function(schema, status, opt) {
		if (status == 'success') {
			schema.model = options.model;
			field = schema.field;
			p = pat.createPage({ 
				id: options.id, title: options.title ? options.title : schema.title, template: 'editTemplate', data: schema,
				dontAttach: options.dontAttach, topButton: schema.canSave ? { title: 'Save' } : null, excludeBack: options.excludeBack
			});
			if (options.value && (options.value != 'new') && (options.value != -1) && (options.value != '-1')) {
				$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'get', value: options.value } }, function(data, status, opt) {
					if (status == 'success') {
						setData(data.data);
						if (callback) callback(p);
					}
				});
			} else {
				var t = {};
				$.each(field, function(f, o) {
					t[o.name] = o.default ? o.default : null;
				});
				setData(t);
				if (callback) callback(p);
			}
		}
	});	
	pat.setTopButtonHandler(doSave);
};

pat.createOvvwPage = function(options, callback) {
	/* options : 
			*model, noCache, title 
			*id, dontAttach, topButton, excludeBack
	*/
	var doNew = function() {
		$.mobile.changePage('page.php?mode=edit&model='+options.model+'&value=new');
	};
	$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'ovvw' } }, function(json, status, opt) {
		if (status == 'success') {
			json.model = options.model;
			var p = pat.createPage({
				id: options.id, title: options.title ? options.title : json.title, template: 'overviewTemplate', data: json,
				dontAttach: options.dontAttach, topButton: json.canCreate ? { title: 'New' } : null, excludeBack: options.excludeBack
			});
			if (callback) callback(p);
		}
	});	
	pat.setTopButtonHandler(doNew);
};

pat.local = {
	set: function(k, v) {
		if ($.support.localStorage) {
			localStorage['collect:'+k] = v;
		} else {
		
		}
		return v;
	},
	get: function(k, v) {
		if ($.support.localStorage) {
			var r = localStorage['collect:'+k];
			if (r == null && v != null) r = v;
			return r;
		} else {
		
		}
		return null;
	}
};

pat.createLognPage = function(options, callback) {
	if (pat.local.get('valve.cookiedate') == null) {
		pat.local.set('valve.cookie', null);
	} else {
		if (pat.local.get('valve.cookiedate')-Math.round((new Date()).getTime()/1000) < 0) {
			pat.local.set('valve.cookie', null);
		}
	}
	$.retrieveJSON('api/page.php', { data: { value: {cookie: pat.local.get('valve.cookie')}, mode: 'strt' } }, function(json, status, opt) {
		if (status == 'success') {
			// expect to recieve: background-url, theme, title, 
			pat.setStart(json); // will save some stuff to localstorage and change background etc.
			if (json.loggedIn == true) {
				// cookie logged in :: skip login screen - go straight to default menu
				pat.handlePageRequest('page.php?mode=menu&model=default',{excludeBack:true});
			} else {
				// if the cookie didn't login, then we want to blank whatever is in localstorage
				pat.local.set('valve.cookie', null);
				pat.local.set('valve.cookiedate', null);
				var p = pat.createPage({ 
					id: options.id, title: options.title ? options.title : json.title, template: 'loginTemplate', data: { email: pat.local.get('valve.email') },
					dontAttach: options.dontAttach, topButton: null, excludeBack: true
				});
				if (callback) callback(p);
			}
		}
	});
};

pat.start = function() {
//	$('#body').css('background-image', 'url('+pat.local.get('valve.backgroundUrl', 'none')+')');
	
};

pat.setStart = function(s) {
	pat.local.set('valve.backgroundUrl', s.backgroundUrl);
	pat.local.set('valve.title', s.title);
	pat.local.set('valve.theme', s.theme);
	pat.start();
};

pat.login = function() {
	var o = {
		email: $('#pfEmail').val(),
		password: $('#pfPassword').val()
	};
	var vo = pat.vjs(o, pat.schema.login);
	if (vo.st == 'ok') {
		pat.displayErrorMessages(null, 'loginScreen');
		$('#pfEmail').textinput('disable');
		$('#pfPassword').textinput('disable');
		$.mobile.loading('show',{textVisible: true, text: "Saving" });
		$.retrieveJSON('api/page.php', { data: { mode: 'login', value: o } }, function(data, status, opt) {
			if (status == 'success') {
//				console.log(data);
				$.mobile.loading('hide');
				$('#pfEmail').textinput('enable');
				$('#pfPassword').textinput('enable');
				if (data.st == 'ok') { // logged in
					pat.local.set('valve.cookie', data.cookie);
					pat.local.set('valve.email', o.email);
					pat.local.set('valve.cookiedate', data.cdate);
					pat.handlePageRequest('page.php?mode=menu&model=default',{excludeBack:true});
				} else { // could not login, display errors
					pat.displayErrorMessages(data, 'loginScreen');
				}
			}
		});
	} else pat.displayErrorMessages(vo, 'loginScreen');
};


/*pat.createErrorPage = function(options, callback) {
	var p = pat.createPage({ 
		id: options.id, title: options.title ? options.title : 'Error', template: 'validationErrorTemplate', data: { msg:['poo'] },
		dontAttach: options.dontAttach, excludeBack: options.excludeBack, dialog: options.dialog
	});
	if (callback) callback(p);
};*/

pat.createMenuPage = function(options, callback) {
	/* options : 
			*model, noCache, title 
			*id, dontAttach, topButton, excludeBack
	*/
	$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'menu' } }, function(json, status, opt) {
		if (status == 'success') {
			json.model = options.model;
			var p = pat.createPage({ 
				id: options.id, title: options.title ? options.title : json.title, template: 'menuTemplate', data: json,
				dontAttach: options.dontAttach, excludeBack: options.excludeBack
			});
			if (callback) callback(p);
		}
	});	
};

pat.createCstmPage = function(options, callback) {
	$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'cstm' } }, function(json, status, opt) {
		if (status == 'success') {
			var p = pat.createPage({ 
				id: options.id, title: options.title ? options.title : (json.title ? json.title : 'Custom Event'), template: 'cstmTemplate', data: json,
				dontAttach: options.dontAttach, excludeBack: options.excludeBack
			});
			if (callback) callback(p);
		}
	});	
};

pat.createActnPage = function(options, callback) {
	$.retrieveJSON('api/page.php', { data: { model: options.model, mode: 'actn', value: options.value } }, function(json, status, opt) {
		if (status == 'success') {
			var p = pat.createPage({ 
				id: options.id, title: options.title ? options.title : (json.title ? json.title : options.value), template: 'cstmTemplate', data: json,
				dontAttach: options.dontAttach, excludeBack: options.excludeBack
			});
			if (callback) callback(p);
		}
	});	
};

pat.createPingPage = function(options, callback) {
	var deb = { startTime: new Date() };
	$.retrieveJSON('api/page.php', { data: { mode: 'ping' } }, function(json, status, opt) {
		if (status !== 'success') {
			deb.cacheTime = new Date();
		}
		if (status == 'success') {
			deb.serverTime = new Date();
			var dat = {
				sec: [
					{ name: 'General', prop: [
						{ k:'Ping Server on', v:deb.startTime },
						{ k:'Cache Response Time', v:deb.cacheTime-deb.startTime+'ms' },
						{ k:'Server Response Time', v:deb.serverTime-deb.startTime+'ms' },
						{ k:'Supp. Ajax', v:$.support.ajax },
						{ k:'Supp. Local Storage', v:$.support.localStorage },
						{ k:'Supp. Touch', v:$.support.touch }
					]},
					{ name: 'Pages in Dom', prop: $.map($('.ui-page'), function(i, m){return {k:m,v:i.id};}) },
					{ name: 'Supports', prop: $.map($.support, function(i, m){return {k:m,v:i};}) }
				]
			};
			if($.support.localStorage)
				dat.sec.push({ name: 'Local Storage', prop: $.map(localStorage, function(i, m){return {k:m,v:i};}), command: [{k:'Clear Local Storage',v:'localStorage.clear();'}] });
			
			var p = pat.createPage({ id: options.id, title: 'Debug Info', template: 'debugTemplate', data: dat, dontAttach: options.dontAttach });
			if (callback) callback(p);
		}
	});	
};

pat.topButton = function() {
	if (pat.topButtonHandler) pat.topButtonHandler();
};
pat.setTopButtonHandler = function(f) {
	if (f) pat.topButtonHandler = f;
};

pat.history = {
	stack: [],
	has: function(pg) {
		for (var i = pat.history.stack.length; i > 0; i--)
			if (pat.history.stack[i-1].id == pg)
				return pat.history.stack[i-1];
		return false;
	},
	log: function(pg) { // id, cachable, 
		pat.history.stack.push(pg);
//		console.log('stack is now:', pat.history.stack);
	}/*,
	getPage: function(pg, callback) {
	
	}*/,
	back: function(steps) { // move back in the stack and remove stepped over pages.
		for (var i = 0; i < steps; i++) pat.history.stack.pop();
//		console.log('stack is now:', pat.history.stack);
	}
};

// prevent default old-school page transition behavior, ajaxify and use json requests for all data.
$(document).bind('pagebeforechange', function(e, data) {
//	console.log('pbf', e, data.options, data.options.reverse);
	if (data.options.reverse) { // user pushed back button
//		console.log('change_back',e, data);
		e.preventDefault();
//		checkHistory();
		return;
	}
	if (typeof data.toPage !== 'string') return;
	if (data.toPage.match(/page.php/)) {
//		console.log('load url', e, data);
		e.preventDefault();
		pat.handlePageRequest(data.toPage, data.options, function(page) {
//			data.deferred.resolve( data.toPage, data.options, page);
		});
	} else
	if (data.toPage.match(/#pg/)) {
//		console.log('change_page',data.toPage, e, data);
		var pg = data.toPage.split('#')[1];
		var tp = pat.history.has(pg);
		if (tp) {
//			console.log('page previously visited, chec kfor cache', tp.cachable);
			if (tp.cachable) { // can use cached version, just swap page element.
				pat.history.back(1); // remove last step, reuse previous step
			} else { // must recollect info from server using original source.
				pat.history.back(2); // remove last two steps, force refresh of previous step
				e.preventDefault();
//				tp.options.reverse = true;    // this made my head sore.... work flow of these pages is very hacky
				tp.options.transition = 'pop'; // so instead of reversing transition (reverse effected workflow - back buttons), just change transition
//				console.log('dir', tp.options.reverse);
				pat.handlePageRequest(tp.src, tp.options, function(page) {});
			}
		} else {
			//pat.history.log({id: pg, cachable: false});
			// how did it get here? every page must first be generated through a call to page.php......
		}
	} else
	if (data.toPage.match(/history.back/)) {
		e.preventDefault();
		console.log('change_back',data.toPage);
		console.log('WANT BACK data:', $.mobile.urlHistory, $.mobile.urlHistory.stack);
	}
});

pat.handlePageRequest = function(url, options, callback) {
	var purl = url.split('?')[1];
	var param = purl.split('&');
	var o = {};
	for (var i = 0; i < param.length; i++) {
		var t = param[i].split('=');
		o[t[0]] = t[1];
	}
//	console.log('hpr', options, o);

	var changePager = function(ops) {
		var pid=ops.id?'pg'+ops.id:'pg-'+o.mode+'-'+o.model/*+(o.value?'-'+o.value:'')*/;
		var dops={ model:o.model, value:o.value, source:o.source, mode:o.mode, id:pid, excludeBack:options.excludeBack };
		var fnc = dops.mode.toLowerCase();
//		console.log('for page create', o, dops);
		pat['create'+fnc[0].toUpperCase()+fnc.substring(1)+'Page'](dops, function(page, cachable) {
// TODO: consider refactoring this function to be called 'preparePage', then move the below back to beforepagechange event, this will keep history management and page transitions in one place.
			$.mobile.changePage('#'+pid, { id: pid, excludeBack: options.excludeBack, reverse: options.reverse, transition: options.transition?options.transition:'slide' });
//			console.log('p',page);
			pat.history.log({id: pid, cachable: cachable?true:false, src: 'page.php?'+purl, options: { id: pid, excludeBack: options.excludeBack, reverse: options.reverse} });
			if (callback) callback(page);
		});
	}
/*	if (o.mode == 'menu')	{
		changePager({id:o.source+o.mode});
	} else {*/
		changePager({});
//	}
};

pat.constant = {
	regex: {
		email: /^\s*[\w\-\+_]+(\.[\w\-\+_]+)*\@[\w\-\+_]+\.[\w\-\+_]+(\.[\w\-\+_]+)*\s*$/,
		password: /^.{1,35}$/,
		name: /^[\.\s\-]+$/i,
		date: /^(19|20)\d\d([- \/.])(0[1-9]|1[012])\2(0[1-9]|[12][0-9]|3[01])$/
	}
};

pat.fromCamelToLabel = function(s){
	return s[0].toUpperCase() + s.substring(1).replace(/[A-Z]/g, function(h){return " "+h;});
};

pat.getFieldDataType = function(t) {
  r = { // must convert to: STRING, NUMBER, BOOLEAN, FLOAT
 		"text": "STRING",
 		"string": "STRING",
 		"varchar": "STRING",
 		"integer": "NUMBER",
 		"int": "NUMBER",
 		"smallint": "NUMBER",
 		"bigint": "NUMBER",
 		"tinyint": "NUMBER",
 		"blob": "NONE",
 		"binary": "NONE",
 		"real": "FLOAT",
 		"double": "FLOAT",
 		"float": "FLOAT",
 		"numeric": "NUMBER",
 		"boolean": "BOOLEAN",
 		"decimal": "NUMBER",
 		"date": "STRING",
 		"datetime": "STRING"
	};
	return r[t.toLowerCase()].toLowerCase();
}

pat.vjs = function(object, schema) {
	var getFieldLabel = function(o, f) {
		if (typeof(o[f].label) == 'string') {
			return o[f].label;
		} else {
			return pat.fromCamelToLabel(f);
		}
	};
//	console.log(object);
	// check for null values
	var r = [];
	for (var n in schema) {
//		console.log(schema, n, object, object[n]);
		if (((object[n] == undefined) || (object[n] == null)) && (!schema[n]['null']))
			r.push(getFieldLabel(schema, n) + ' cannot be null.');
	}
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:1 };
		return t;
	}
	
	// check data types
	r = [];
	for (var n in schema)
		if ((object[n] != undefined) && (object[n] != null)) {
//			console.log('data type', schema[n].type);
			if ((schema[n].type!='rel-many') && (pat.getFieldDataType(schema[n].type)!=typeof(object[n])))
				r.push(getFieldLabel(schema, n) + ' is the wrong data type.');
		}
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:2 };
		return t;
	}
	
	// check required fields are not blank, then also check fields against validations
	// validation function for attributes
	var valAttr = function(t, s) {
		var r = [];
		if ((t != undefined) && (t != null)) {
			if (s.type == 'string') {
				if (t == '') {
					if (s.req)   r.push(getFieldLabel(schema, n) + ' is required.');
				} else {
//					if (s.regex) console.log(getFieldLabel(schema, n), s.regex, pat.constant.regex[s.regex], t.search(pat.constant.regex[s.regex]));
					if (s.regex) if (t.search(pat.constant.regex[s.regex]) == -1) r.push(getFieldLabel(schema, n) + ' is incorrectly formatted.');
					if (s.min)   if (t.length < s.min) r.push(getFieldLabel(schema, n) + ' is too short. (Min '+s.min+')');
					if (s.max)   if (t.length > s.max) r.push(getFieldLabel(schema, n) + ' is too long. (Max '+s.max+')');
				}
			}
			if (s.type == 'number') {
				if (s.min && (t < s.min)) r.push(getFieldLabel(schema, n) + ' is too small. (Min '+s.min+')');
				if (s.max && (t > s.max)) r.push(getFieldLabel(schema, n) + ' is too big. (Max '+s.max+')');
			}
			if (s.type == 'object') {
				if (s.schema != undefined) {
					var y = pat.vjs(t, s.schema);
					if (y.st == 'er') y.msg.each(function(mi){r.push(mi);});
				}
			}
		}
		return r;
	};
	r = [];
	for (var n in schema) { // now run through attributes that should be present and validate them with above function.
		var t = object[n];
		var s = schema[n];
		if ((t != undefined) && (t != null)) {
			if (s.array) { // if is an array, then run validation on every element
				for (i = 0; i<t.length && r.length==0; i++) {
					var y = valAttr(t[i], s);
					if (y.length!=0) $(y).each(function(l, mi){r.push(mi);});
				}
			} else {
				var y = valAttr(t, s);
				if (y.length!=0) $(y).each(function(l, mi){r.push(mi);});
			}
		}
	}
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:3 };
		return t;
	}
	
	// check custom functions
	r = [];
	for (var n in schema) {
		var s = schema[n];
		if (s.custom)
			r.concat(s.custom(object[n], object));
	}
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:4 };
		return t;
	}

	return { st: 'ok' };
};


$(document).ready(function($) {
	pat.start();
//  pat.handlePageRequest('page.php?mode=ping',{});
	pat.handlePageRequest('page.php?mode=logn',{});
//	pat.handlePageRequest('page.php?mode=view&model=user',{excludeBack:true});
//	$(document).on( 'swipe', function() {});
});

$(document).bind('swipe', function() {
	//document.location.reload(true);
});


$(document).bind('taphold',function(event, ui){
	if (event.target) {
		if (typeof event.target.className !== 'string') return;
		if (event.target.className.indexOf('heading') > -1)
			if (event.target.className.indexOf('ui-title') > -1) {
				pat.handlePageRequest('page.php?mode=ping',{});
			}
	}
});

</script>
</head><body id="body">
	<!-- Login -->
	<div data-role="page" id="pgError"></div>
		

</body></html>