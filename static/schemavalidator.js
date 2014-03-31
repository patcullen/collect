// used in schema valiadtion for json objects : for returning a pretty label of incorrect field
String.prototype.fromCamelToLabel = function(){
	return this[0].toUpperCase() + 
		this.substring(1).replace(/[A-Z]/g, function(h){return " "+h;});
}

vjs = function(object, schema, id) {
	var getFieldLabel = function(o, f) {
		if (typeof(o[f].label) == 'string') {
			return o[f].label;
		} else {
			return f.fromCamelToLabel();
		}
	};
	// check for null values
	var r = [];
	for (var n in schema)
		if (((object[n] == undefined) || (object[n] == null)) && (!schema[n]['null']))
			r.push(getFieldLabel(schema, n) + ' cannot be null.');
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:1 };
		if ((id != undefined) && (id != null)) t.i = id;
		return t;
	}
	
	// check data types
	r = [];
	for (var n in schema)
		if ((object[n] != undefined) && (object[n] != null))
			if (schema[n].type != typeof(object[n]))
				r.push(getFieldLabel(schema, n) + ' is the wrong data type.');
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:2 };
		if ((id != undefined) && (id != null)) t.i = id;
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
					if (s.regex) if (t.search(s.regex) == -1) r.push(getFieldLabel(schema, n) + ' is incorrectly formatted.');
					if (s.min)   if (t.length < s.min) r.push(getFieldLabel(schema, n) + ' is too short.');
					if (s.max)   if (t.length > s.max) r.push(getFieldLabel(schema, n) + ' is too long.');
				}
			}
			if (s.type == 'number') {
				if (s.min && (t < s.min)) r.push(getFieldLabel(schema, n) + ' is too small.');
				if (s.max && (t > s.max)) r.push(getFieldLabel(schema, n) + ' is too big.');
			}
			if (s.type == 'object') {
				if (s.schema != undefined) {
					var y = valve.vjs(t, s.schema, id);
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
					if (y.length!=0) y.each(function(mi){r.push(mi);});
				}
			} else {
				var y = valAttr(t, s);
				if (y.length!=0) y.each(function(mi){r.push(mi);});
			}
		}
	}
	if (r.length > 0) {
		var t = { st: 'er', msg: r, s:3 };
		if ((id != undefined) && (id != null)) t.i = id;
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
		if ((id != undefined) && (id != null)) t.i = id;
		return t;
	}

	return { st: 'ok', i: id };
};


valve.schema.login = {
	email: { type: 'string', req: 1, regex: valve.constant.regex.email },
	password: { type: 'string', req: 1, min: 6, max: 99 },
	rememberMe: { type: 'boolean' }
};

valve.schema.loginAuto = {
	email: { type: 'string', req: 1, regex: valve.constant.regex.email },
	cookie: { type: 'string', req: 1, min: 20, max: 99 }
};

valve.schema.repoFolder = {
	i: { type: 'string', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric, custom: function(v,o) {
		if ((o.i==null)&&(o.p==null))
			return ['A folder must specify either an Id or a parent to be saved into.'];
		return [];
	} },
	n: { type: 'string', label: 'Name', min: 1, max: 255, regex: valve.constant.regex.alphaNumeric },
	a: { type: 'number', label: 'Access Level', min: 0, max: 2 },
	p: { type: 'string', label: 'Parent', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric }
};

valve.schema.sketchHeader = {
	i: { type: 'number', label: 'Id', min: 0 },
	n: { type: 'string', label: 'Name', min: 1, max: 120, regex: valve.constant.regex.alphaNumeric },
	t: { type: 'number', label: 'Data Type', min: 0, max: 6 },
	r: { type: 'boolean', label: 'Required' },
	p: { type: 'number', label: 'Position', min: 0 },
	m: { type: 'number', label: 'Dimension', min: 0, max: 1 },
	d: { type: 'string', label: 'Direction', custom: function(v) {
		if ((v!='in')&&(v!='out'))
			return ['Direction must be set to \'in\' or \'out\'.'];
		return [];
	} }
};

valve.schema.sketch = {
	i: { type: 'string', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric, custom: function(v,o) {
		if ((o.i==null)&&(o.p==null))
			return ['A folder must specify either an Id or a parent folder to be saved into.'];
		return [];
	} },
	p: { type: 'string', label: 'Parent', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric },
	n: { type: 'string', label: 'Name', min: 1, max: 255, regex: valve.constant.regex.alphaNumeric },
	d: { type: 'string', label: 'Description', min: 0, max: 1024 },
	f: { type: 'object', label: 'Parameters', array: true, schema: valve.schema.sketchHeader, 
		custom: function(v, t) {
			for (i = t.f.length-1; i >= 0; i--)
				for (j = 0; j < i; j++)
					if (t.f[i].i == t.f[j].i) return ['All parameters must have a unique id.'];
			return [];
		}
	},
	t: { type: 'number', label: 'Data Type', min: 0, max: 6 },
	m: { type: 'number', label: 'Dimension', min: 0, max: 1 },
	r: { type: 'string', label: 'Special Role', min: 0, max: 20 },
	x: { type: 'number', label: 'Native', min: 0, max: 1 },
	a: { type: 'number', label: 'Access Level', min: 0, max: 2 }
};

valve.schema.sketchNative = {
	i: { type: 'string', min: 15, max: 25, regex: valve.constant.regex.alphaNumeric },
	c: { type: 'string', max: 8000 }
};

valve.schema.sketchDesign = {
	i: { type: 'string', min: 15, max: 25, regex: valve.constant.regex.alphaNumeric }
	//c: { type: 'string', max: 8000 }
};

valve.schema.task = {
	i: { type: 'string', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric, custom: function(v,o) {
		if ((o.i==null)&&(o.p==null))
			return ['A task must specify either an Id or a parent node to be saved into.'];
		return [];
	} },
	n: { type: 'string', label: 'Name', req: true, min: 2, max: 255, regex: valve.constant.regex.aAlphaNumeric },
	p: { type: 'string', label: 'Parent', null: true, min: 15, max: 25, regex: valve.constant.regex.alphaNumeric },
	s: { type: 'string', label: 'Sketch', min: 15, max: 25, regex: valve.constant.regex.alphaNumeric },
	x: { type: 'object', label: 'Parameters', array: true },
	a: { type: 'number', label: 'Active', min: 0, max: 1 },
	c: { type: 'string', label: 'Cron Pattern', min: 11, max: 255, regex: valve.constant.regex.cronTab }	
};



