/* GLOBALS */
var $methods = null;
var $supported_attributes;
var $query_results = {};

/* DOCUMENTREADY */
$(function () {
  $('#load_methods_button').on('click',onLoadMethodsButtonClick);
  /* AUTOMATION */
  onLoadMethodsButtonClick();
});

String.prototype.tableize = function () {
  if (this.indexOf("get_") == 0) {
    return this.replace("get_", "");
  } else {
    return "tableizeInputStringNotSupported";
  }
};

String.prototype.titleize = function () {
  return this.replace(/_/g," ").replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
};

var reveal = function (m) {
  return Object.prototype.toString.call(m);
}

String.prototype.classify = function () {
  var str = "";
  str = this.replace("get_", "").titleize().replace(/ /g, "").slice(0, -1);
  if (this.substring(this.length - 3, this.length) == "ies") {
    str = str.substr(0, str.length - 3) + "ry";
  } else if (this.substring(this.length - 2, this.length) == "es" && this != "get_images") {
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

function getRequest(req, callback, options) {
  var s = getSettings();
  //$.getJSON(s.url, req, onGetRequestComplete );
  $.ajax({
    type: "POST",
    url: s.url,
    data: JSON.stringify(req),
    contentType: 'application/json',
    success: function(data) {
      callback(data, options);
      displayDebug(data);
      console.log("getRequest RECEIVED:", data);
    },
    dataType: 'json',
    error: function (xhr,type) { console.log( "ERROR", xhr, type)},
  });
  console.log("getRequest SENT:", req);
}


/* ============================ */
/* GET BUTTON FUNCTIONS BEGIN */
/* ============================ */

function onLoadMethodsButtonClick() {
  $('#results').html('');
  $('#arguments').html('');
  var request = prepareRequest('get_api_methods');
  getRequest(request, displayAPIMethods);
}


function onGetMethodButtonClick(proc) {
  //console.log("onMethodButtonClick", proc);
  $('#results').html('');
  $('#arguments').html('');
  var request;
  var div = $('#arguments');
  var tmpl = _.template($('#argument_template').html());
  var tmpl_select = _.template($('#argument_template_select').html());
  var query_args = [];
  
  switch(proc) {
    case "get_system_time":
      // this one is special and needs individual rendering
      request = prepareRequest(proc);
      getRequest(request, displaySystemTime );
      break;
      
    case "get_supported_attributes":
      // this one is special and needs individual rendering
      request = prepareRequest(proc);
      getRequest(request, displaySupportedAttributes );
      break;
      
    default:
      // this one displays various klasses delivered by the backend, e.g. Product, Category, etc.
      if ($methods[proc]) {
        //
        $.each($methods[proc], function(field, desc) {
          if (desc.values) {
            // is an array
            query_args.push({
              columns: desc.sizehint + 2,
              id: field,
              label: field.titleize(),
              placeholder: desc.default,
              options: desc.values,
              type: desc.type,
            });
          } else {
            query_args.push({
              columns: desc.sizehint + 2,
              id: field,
              label: field.titleize(),
              placeholder: desc.default,
              type: desc.type,
            });
          };
        });
      }
      for ( var i = 0; i < query_args.length; i++ ) {
        if (query_args[i].options) {
          div.append( tmpl_select( query_args[i] ) );
        } else {
          div.append( tmpl( query_args[i] ) );
        }
      }
      var button = $('<div class="small button">Load</div>');
      button.on('click', function () {
        var request = prepareRequest(proc);
        // now, add the query arguments to the request
        for ( var i = 0; i < query_args.length; i++ ) {
          var arg = query_args[i];
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
      break;
  }
}






/* ============================ */
/* DISPLAY CALLBACKS AFTER REQUEST */
/* ============================ */

function displayDebug( data ) {
  console.log(data);
  var req_vars = ['action','proc','arguments','errors','warnings','notifications','status'];
  var div = $('#request_debug');
  div.html('');
  for ( var i = 0; i < req_vars.length; i++ ) {
    div.append('<h5>' + req_vars[i].titleize() + '</h5>');
    div.append('<pre>' + JSON.stringify(data[req_vars[i]],undefined,2) + '</pre>');
  }
  div.effect("highlight");
  div = $('#payload_debug');
  div.html('');
  div.append('<pre>' + JSON.stringify(data[ 'payload' ],undefined,2) + '</pre>');
  div.effect("highlight");
}

function displayAPIMethods( data ) {
  $('#methods').html('');
  $methods = data.payload;
  for (var key in data.payload ) {
    var button = $('<div class="small button">' + key.titleize() + '<div>');
    
    if ( key.indexOf('set_') == 0) {
      console.log("XXXXX", key);
      button.addClass('alert');
      button.on('click', function(method) {
        return function() {
          onSetMethodButtonClick(method);
        }
      }(data.proc));
    }
    
    if ( key.indexOf('get_') == 0) {
      button.on('click', function(k) {
        return function() {
          onGetMethodButtonClick(k);
        }
      }(key));
    }
    
    $('#methods').append(button);
    $('#methods').append('<br />');
  }

  /* AUTOMATION CODE */
  // After the "Load Methods" button has been clicked, automatically trigger loading of supported attributes
  onGetMethodButtonClick("get_supported_attributes");
  //onGetMethodButtonClick("get_products");
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
  
  if (data.errors.length > 0) {
    var tmpl_error = _.template($('#response_errors').html());
    $('#results').append(tmpl_error({ messages: data.errors }));
  }
  
  if (data.warnings.length > 0) {
    var tmpl_warning = _.template($('#response_warnings').html());
    $('#results').append(tmpl_warning({ messages: data.warnings }));
  }
  
  if (data.notifications.length > 0) {
    var tmpl_notification = _.template($('#response_notifications').html());
    $('#results').append(tmpl_warning({ messages: data.notifications }));
  }
  
  var tmpl_notice = _.template($('#statusmessage_template').html());
  var statusmessage = "Received payload of length " + data.payload.length;
  $('#results').append(tmpl_notice({ statusmessage: statusmessage }));
  
  json_path = [data.proc.replace("get_", "")];
  klass = data.proc.classify();
  if ( $supported_attributes[klass] ) {
    var html_result = renderEditFields(data.payload, data.proc.tableize(), 0, json_path);
    $("#results").html($("#results").html() + html_result);
    $(".datepicker").datepicker();
    $(".editme").on('change', onInputChanged);
  } else {
    var statusmessage = "WARNING: The method 'get_supported_attributes' does not define Klass '" + data.proc.classify() + "'. Inplace-editing of fields is therefore not supported. Simply displaying values instead.";
    $("#results").append(tmpl_notice({ statusmessage: statusmessage }));
    renderEditTable($('#results'), data.payload);
  }
}


/* SET FUNCTIONS */
function onSetMethodButtonClick(proc) {
  json_path = [proc.replace("set_", "")];
  klass = proc.classify();
  console.log("onSetMethodButtonClick called with", proc, klass);
  $.each($supported_attributes[klass], function(attr, desc) {
    $("#response").append(attr);
  });
}




/* ============================ */
/* HELPER FUNCTIONS */
/* ============================ */


function renderEditTable(parent_element, collection) {
  var table = $(document.createElement('table'));
  parent_element.append(table);
  var header = $(document.createElement('tr'));
  table.append(header);

  if (reveal(collection) == "[object Array]" && collection[0]) {
    // render a header from the keys in the first element
    $.each(collection[0], function(k,v) {
      var th = $(document.createElement('th'));
      th.html(k);
      header.append(th);
    });
  } else if (reveal(collection) == "[object Object]") {
    // The rendering code below is made for Arrays only. If we get an Object instead, render Warning and wrap object in an Array.
    $.each(collection, function(k,v) {
      var th = $(document.createElement('th'));
      th.html(k);
      header.append(th);
    });
    var warning = $(document.createElement('p'));
    warning.css("color", "red");
    warning.html("WARNING: Backend returned object instead of Array");
    collection = [collection];
    warning.insertBefore(table);
  }

  $.each(collection, function(idx,val) {
    // render a row for each array entry
    var row = $(document.createElement('tr'));
    $.each(val, function(k,v) {
      var col = $(document.createElement('td'));
      if (reveal(v) == "[object Object]" || reveal(v) == "[object Array]") {
        // start recursion
        renderEditTable(col, v);
      } else {
        var span = $(document.createElement('span'));
        span.html(v);
        col.append(span);
        table.append(row);
      }
      row.append(col);
    });
  });
}

function renderEditFields(collection, table, depth, json_path) {
  //console.log("RENDER CALLED", collection, table, depth, json_path);
  var klass = table.classify();
  //console.log("KLASS BEGINNING", klass);
  
  
  var html_result = '';

  var data;
  var tmpl_attr     = _.template($('#attribute_template').html());
  var tmpl_attr_error = _.template($('#attribute_template_error').html());
  var tmpl_attr_row = _.template($('#attribute_row_template').html());
  var tmpl_header   = _.template($("#attribute_row_heading_template").html());
  
  for (var i = 0; i < collection.length; i++) {
    var model = collection[i];
    //console.log("Model",model);
    var cols = '';
    var record_id = model.id;
    for (var key in model) {

      if ( reveal(model[key]) == '[object Array]') {
        // display HAS_MANY relationship in place, via Recursion
        var rendered_hasmany_header = tmpl_header({
          klass: key.titleize(),
          depth: depth + 3,
        });
        depth += 1;
        json_path.push(key);
        var v = renderEditFields(model[key], key, depth, json_path);
        depth -= 1;
        v = rendered_hasmany_header + v;
        tmpl_data = {
          columns: 22, 
          value: v,
          key: key,
          nested: true,
          depth: depth,
          record_id: 0,
          klass: 'nested',
        };
        //console.log("tmpl_data is", tmpl_data);
        cols += tmpl_attr(tmpl_data);
        
      } else {
        // simple datatypes
        
        if ( ! $supported_attributes[klass] ) {
          var msg = "Klass '" + klass + "' not defined by the response of  'get_supported_attributes' method. Skipping.";
          cols += tmpl_attr_error({ error_message: msg });
          continue;
        }
        
        if ( ! $supported_attributes[klass][key] ) {
          var msg = "Attribute '" + key + "' in klass '" + klass + "' not defined by the response of get_supported_attributes method. Skipping.";
          cols += tmpl_attr_error({ error_message: msg });
          continue;
        }
        
        var desc = $supported_attributes[klass][key];
        
        tmpl_data = {
          columns: Math.floor(desc.sizehint * 22 / 10), 
          value: model[key],
          values: desc.values,
          key: key,
          type: desc.type,
          nested: false,
          json_path: table + "." + key,
          record_id: record_id,
          klass: klass,
        };
        
        // output error if types don't match
        if (desc.type == "array" && reveal(model[key]) != "[object " + desc.type.titleize() + "]") {
          var msg = "ERROR! Key '" + key + "' of Klass '" + klass + "' is supposed to be a " + desc.type.titleize() + " but we got a " + reveal(model[key]) + " from the backend.";
          cols += tmpl_attr_error({ error_message: msg });
          continue;
        }
        cols += tmpl_attr(tmpl_data);
      }
    }
    
    var rendered_header = tmpl_header({
      klass: klass,
      depth: depth + 1,
    });
    var rendered_row = tmpl_attr_row({
      value: cols,
      klass: klass,
    });
    
    // prepend header for each new record
    if (depth == 0) {
      html_result += rendered_header + rendered_row;
    } else {
      html_result += rendered_row;
    }
  }
  return html_result;
}

function onInputChanged() {
  var input = $(this);
  var json_path = input.attr("json_path").split(".");
  
  //console.log("onInputChanged", input.val());
  var request = prepareRequest();
  request.proc = "set_" + json_path.shift();
  //json_path.shift();
  request.payload = [{
    id: input.attr("record_id"),
  }];
  setDeepHashValue(request.payload[0], json_path.join("."), input.val());
  getRequest(request, inputChangedComplete, {element: input});
  
}

function inputChangedComplete(data, options) {
  //console.log("CHANGED COMPLETE", data, options.element);
  var msg;
  
  if (data.status == true) {
    msg = "Request '" + data.proc + "' succeeded!";
  } else {
    msg = "Request '" + data.proc + "' did not succeed.";
  }
  options.element.effect('highlight');
  var tmpl_statusmessage = _.template($('#statusmessage_template').html());
  var rendered_statusmessage = tmpl_statusmessage({
    statusmessage: msg,
  });
  $(rendered_statusmessage).insertAfter(options.element);
  
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