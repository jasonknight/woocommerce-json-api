/* GLOBALS */
var $methods = null;
var $supported_attributes;
var $query_results = {};

String.prototype.titleize = function () {
  return this.replace(/_/g," ").replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
};

var reveal = function (m) {
  return Object.prototype.toString.call(m);
}

String.prototype.classify = function () {
  var str = "";
  str = this.replace("get", "").titleize().replace(/ /g, "").slice(0, -1);
  if (this.substring(this.length - 3, this.length) == "ies") {
    str = str.substr(0, str.length - 3) + "ry";
  } else if (this.substring(this.length - 2, this.length) == "es") {
    str = str.slice(0, -1);
  }
  return str;
};

function valOrPlaceholder(id) {
  var v = $(id).val();
  if ( v == "" ) {
    v = $(id).attr('placeholder');
  }
  return v;
}


function getSettings() {
  var s = {
    url:      valOrPlaceholder('#url'),
    token:    valOrPlaceholder('#token'),
    username: $('#username').val(),
    password: $('#password').val()
  };
  return s;
}

function prepareRequest(name,existing_req) {
  var req = {};
  if ( existing_req )
    req = existing_req;
  var s = getSettings();
  req.action = "woocommerce_json_api";
  req.proc = name;
  req.arguments = {};
  if ( s.username !== "" ) {
    req.arguments.username = s.username;
    req.arguments.password = s.password;
  } else {
    req.arguments.token = s.token;
  }
  return req;
}

function getRequest(req, callback) {
  var s = getSettings();
  //$.getJSON(s.url, req, onGetRequestComplete );
  $.ajax({
    type: "POST",
    url: s.url,
    data: JSON.stringify(req),
    contentType: 'application/json',
    success: callback,
    dataType: 'json',
    error: function (xhr,type) { console.log( "ERROR", xhr, type)},
  });
  console.log("Sent", req);
}


/* ============================ */
/* GET BUTTON FUNCTIONS BEGIN */
/* ============================ */

$(function () {
  $('#load_methods_button').on('click',onLoadMethodsButtonClick);
});

function onLoadMethodsButtonClick() {
  var request = prepareRequest('get_api_methods');
  getRequest(request, displayAPIMethods);
}

function onGetSystemTimeButtonClick() {
  var request = prepareRequest('get_system_time');
  getRequest(request, displaySystemTime );
}

function onGetSupportedAttributesButtonClick() {
  var request = prepareRequest('get_supported_attributes');
  request.arguments.resources = ['Product','Order','Category','Customer','OrderItem'];
  getRequest(request, displaySupportedAttributes);
}



function onGetProductsButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var tmpl_select = _.template($('#argument_template_select').html());
  var args = [];
  $.each($methods.get_products, function(field, desc) {
    if (desc.values) {
      // is an array
      args.push({
        columns: desc.sizehint + 2,
        id: field,
        label: field.titleize(),
        placeholder: desc.default,
        options: desc.values,
        type: desc.type,
      });
    } else {
      args.push({
        columns: desc.sizehint + 2,
        id: field,
        label: field.titleize(),
        placeholder: desc.default,
        type: desc.type,
      });
    };
      
  });
  
  for ( var i = 0; i < args.length; i++ ) {
    if (args[i].options) {
      div.append( tmpl_select( args[i] ) );
    } else {
      div.append( tmpl( args[i] ) );
    }
  }
  var button = $('<div class="small button">Load Products</div>');
  button.on('click', function () {
    var request = prepareRequest('get_products');
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if ( arg.type == "array" && ! arg.options ) {
        val = $('#' + arg.id).val();
        if ( val == "" )
          continue;
        val = val.split(',');
      } else {
        val = $('#' + arg.id).val();
        if ( val == ""  )
          continue;
      }
      request.arguments[arg.id] = val;
    }
    getRequest(request, displayModel);
  });
  div.append("<hr />")
  div.append(button);
}

function onGetCategoriesButtonClick() {
  var request = prepareRequest('get_categories');
  getRequest( request, onGetRequestComplete );
}

function onGetCategoriesButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var tmpl_select = _.template($('#argument_template_select').html());
  var args = [];
  $.each($methods.get_categories, function(field, desc) {
    if (desc.values) {
      // is an array
      args.push({
        columns: desc.sizehint + 2,
        id: field,
        label: field.titleize(),
        placeholder: desc.default,
        options: desc.values,
        type: desc.type,
      });
    } else {
      args.push({
        columns: desc.sizehint + 2,
        id: field,
        label: field.titleize(),
        placeholder: desc.default,
        type: desc.type,
      });
    };
  });
  
  for ( var i = 0; i < args.length; i++ ) {
    if (args[i].options) {
      div.append( tmpl_select( args[i] ) );
    } else {
      div.append( tmpl( args[i] ) );
    }
  }
  
  var button = $('<div class="small button">Load Categories</div>');
  button.on('click', function () {
    var request = prepareRequest('get_categories');
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if (arg.type == "array" && ! arg.options) {
        val = $('#' + arg.id).val();
        if ( val == "" )
          continue;
        val = val.split(',');
      } else {
        val = $('#' + arg.id).val();
        if ( val == ""  )
          continue;
      }
      request.arguments[arg.id] = val;
    }
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onGetCouponsButtonClick() {
  console.log('called');
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var button = $('<div class="small button">Load Coupons</div>');
  button.on('click', function () {
    var request = prepareRequest('get_coupons');
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onGetTaxesButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var button = $('<div class="small button">Load Taxes</div>');
  button.on('click', function () {
    var request = prepareRequest('get_taxes');
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onGetShippingMethodsButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var button = $('<div class="small button">Load Shipping Methods</div>');
  button.on('click', function () {
    var request = prepareRequest('get_shipping_methods');
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onGetPaymentGatewaysButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var button = $('<div class="small button">Load Payment Gateways</div>');
  button.on('click', function () {
    var request = prepareRequest('get_payment_gateways');
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onSetProductsButtonClick() {
  $('#results').html('');
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var tmpl_txt = _.template($('#argument_template_text').html());
  var select_tmpl = _.template($('#argument_template_select').html());
  //console.log($supported_attributes);
  var args = [];
  var field;
  $.each($supported_attributes.Product, function(attr, desc) {
    if ( ! desc.sizehint ) {
      desc.sizehint = 3;
    } else {
      desc.sizehint += 2;
    }
    if (desc.values) {
      field = {
        columns: desc.sizehint,
        id: attr,
        label: attr.titleize(),
        options: desc.values,
        placeholder: desc.default,
        
      };
    } else {
      field = {
        columns: desc.sizehint,
        id: attr,
        label: attr.titleize(),
        placeholder: desc.default,
        input_type: desc.type
      }
    }
    args.push(field);
  });

  for ( var i = 0; i < args.length; i++ ) {
    if ( args[i].options ) {
      div.append( select_tmpl( args[i] ) );
    } else {
      if ( args[i].input_type == 'text') {
        var rendered_template = tmpl_txt( args[i] );
        div.append( rendered_template );
      } else {
        var rendered_template = $(tmpl( args[i] ));
        if ( args[i].id == 'name' ) {
          rendered_template.find('input').on('keyup', function() {
            var str = $(this).val().replace(/\s+/g, '-').toLowerCase();
            $('#slug').val(str);
          });
        }
        div.append( rendered_template );
      }
    }
  }
  var button = $('<div class="small button">Create Product</div>');
  button.on('click', function () {
    var request = prepareRequest('set_products');
    var obj = {};
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if (typeof arg.placehold == 'string' && arg.placeholder.indexOf(',') != -1) {
        val = $('#' + arg.id).val();
        val = val.split(',');
      } else {
        val = valOrPlaceholder('#' + arg.id);
      }
      obj[arg.id] = val;
    }
    request.payload = [obj];
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}



/* ============================ */
/* DISPLAY CALLBACKS AFTER REQUEST */
/* ============================ */

function displayDebug( data ) {
  console.log(data);
  var req_vars = ['action','arguments','errors','warnings','notifications','status'];
  var div = $('#request_debug');
  div.html('');
  for ( var i = 0; i < req_vars.length; i++ ) {
    div.append('<h5>' + req_vars[i].titleize() + '</h5>');
    div.append('<pre>' + JSON.stringify(data[req_vars[i]],undefined,2) + '</pre>');
  }
  div = $('#payload_debug');
  div.html('');
  div.append('<pre>' + JSON.stringify(data[ 'payload' ],undefined,2) + '</pre>');
}

function displayAPIMethods( data ) {
  $('#methods').html('');
  $methods = data.payload;
  for (var key in data.payload ) {
    var button = $('<div class="small button">' + key.titleize() + '<div>');
    if ( key.indexOf('set_') == 0) {
      button.addClass('alert');
    }
    var method = key.titleize();
    method = 'on' + method.replace(/\s/g,'') + "ButtonClick";
    button.on('click',window[method]);
    $('#methods').append(button);
    $('#methods').append('<br />');
  }
}

function displaySystemTime( data ) {
  var div = $('#results');
  div.html('');
  var d = data.payload[0].date.split('-');
  var t = data.payload[0].time.split(':');
  var date = new Date(d[0],d[1]-1,d[2],t[0],t[1],t[2]);
  div.html("The current date is: " + data.payload[0].date + " and the time is: " + data.payload[0].time);
  div.append('<br /><br /><strong>The parsed time is: ' + date + '</strong>');
}

function displaySupportedAttributes(data) {
  $supported_attributes = data.payload[0];
  $('#results').html("Supported attributes have been saved to global JS variable '$supported_attributes'. You can inpsect it in your console.");
}


function displayModel(data) {
  $('#results').html('');
  json_path = [data.proc.replace("get_", "")];
  //renderEditTable($('#results'), json_path, null, data.payload);
  var html_result = renderEditFields(data.payload, data.proc.classify(), 0);
  $("#results").html(html_result);
}





/* ============================ */
/* HELPER FUNCTIONS */
/* ============================ */


function renderEditTable(parent_element, json_path, model_id, collection) {
  
  var table = $(document.createElement('table'));
  var header = $(document.createElement('tr'));

  if (Object.prototype.toString.call(collection[0]) == "[object Object]") {
    // render a header only for objects, i.e. no arrays or simple types
    $.each(collection[0], function(k,v) {
      var th = $(document.createElement('th'));
      th.html(k);
      header.append(th);
    });
    table.append(header);
  }
  
  $.each(collection, function(key,val) {
    var row = $(document.createElement('tr'));
    
    if (model_id == null) {
      // only execute during the first call of this function
      console.log("setting model_id " + val.id);
      model_id = val.id;
    }
    
    row.attr('model_id', model_id);
    
    if (typeof val == "object") {
      // iterator for arrays of objects and objects
      $.each(val, function(k,v) {
        var col = $(document.createElement('td'));
        row.append(col);
        if (typeof v == "object" && v.length > 0) {
          json_path.push(key);
          json_path.push(k);
          renderEditTable(col, json_path, model_id, v); // recursion
        } else {
          var input = $(document.createElement('input'));
          input.val(v);
          var json_path_string = json_path.join(".") + "." + key + "." + k;
          input.attr('json_path', json_path_string);
          input.attr('model_id', model_id);
          input.on('change', onInputChanged);
          var width = v.length * 6 + 10;
          if (width < 50)
            width = 50;
          input.css('width', width);
          col.append(input);
          table.append(row);
        }
      });
    } else {
      // for simple types, i.e. arrays that only contain strings
      var input = $(document.createElement('input'));
      input.val(val);
      var json_path_string = json_path.join(".") + "." + key;
      input.attr('json_path', json_path_string);
      input.attr('model_id', model_id);
      var width = val.length * 6 + 10;
      if (width < 50)
        width = 50;
      input.css('width', width);
      var col = $(document.createElement('td'));
      col.append(input);
      row.append(col);
      table.append(row);
    }
  });
  parent_element.append(table);
  
}

function renderEditFields(collection, klass, depth) {
  //console.log('Collection',collection);
  var html_result = '';
  if ( collection.length == 0 ) {
    return html_result;
  }
  if ( ! $supported_attributes[klass] ) {
    return 'Editing not supported';
  }
  var data;
  var tmpl_attr     = _.template($('#attribute_template').html());
  var tmpl_attr_row = _.template($('#attribute_row_template').html());
  for (var i = 0; i < collection.length; i++) {
    var model = collection[i];
    //console.log("Model",model);
    var cols = '';
    for ( var key in model) {

      //console.log("Key", key, reveal(model[key]));
      if ( key == 'id' ) {
        continue;
      }
      if ( reveal(model[key]) == '[object Array]' && model[key].length > 0) {
        //console.log("I should recurse with", model[key],'as', key.classify());
        var v = renderEditFields(model[key],key.classify());
         data = {
          columns: 22, 
          value: v,
          key: key,
        };
        cols += tmpl_attr(data);
      } else {
        var desc = $supported_attributes[klass][key];
        if ( ! desc ) { continue; }
        
        data = {
          columns: Math.floor(desc.sizehint * 22 / 10), 
          value: model[key],
          key: key,
        };
        cols += tmpl_attr(data);
      }
    }
    html_result += tmpl_attr_row({ value: cols }); 
  }
  return html_result;
}

function onInputChanged() {
  var input = $(this);
  var json_path = input.attr("json_path").split(".");
  
  console.log("onInputChanged", input.val());
  var request = prepareRequest();
  request.proc = "set_" + json_path.shift();
  //json_path.shift();
  request.payload = [{
    id: input.attr("model_id"),
  }];
  setDeepHashValue(request.payload, json_path.join("."), input.val());
  console.log("SET", request);
  getRequest(request, inputChangedComplete(input));
  
}

function inputChangedComplete(data) {
  data.effect('highlight');
  console.log('completed', data);
  
}

function setDeepHashValue(obj, path, value) {
  var parts = path.split('.');
  var i, tmp;
  for(i = 0; i < parts.length; i++) {
      tmp = obj[parts[i]];
      if(value !== undefined && i == parts.length - 1) {
          tmp = obj[parts[i]] = value;
      }
      else if(tmp === undefined) {
          tmp = obj[parts[i]] = {};
      }
      obj = tmp;
  }
  return obj;
}