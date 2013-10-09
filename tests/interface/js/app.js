/* GLOBALS */
var $methods = null;
var $supported_attributes;
var $query_results = {};

var tmpl_attr;
var tmpl_attr_row;
var tmpl_header;
var tmpl_message;
var tmpl_message_response;

/* DOCUMENTREADY */
$(function () {
  
  tmpl_attr = _.template($("#template_attribute").html());
  tmpl_message_response    = _.template($("#template_message_response").html());
  tmpl_message = _.template($("#template_message").html());
  tmpl_attr_row = _.template($("#template_row_attribute").html());
  tmpl_header = _.template($("#template_attribute_heading").html());
  
  $('#load_methods_button').on('click',onLoadMethodsButtonClick);
  /* AUTOMATION */
  onLoadMethodsButtonClick();
});

String.prototype.tableize = function () {
  if ( this.indexOf("get_") == 0 ) {
    return this.replace("get_", "");
  } else if ( this.indexOf("set_") == 0 ) {
    return this.replace("set_", "");
  } else {
    return "tableizeInputStringNotSupported";
  }
};

String.prototype.titleize = function () {
  return this.replace(/_/g," ").replace(/\w\S*/g, function(txt){
    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
  });
};

var reveal = function (m) {
  return Object.prototype.toString.call(m);
}

String.prototype.classify = function () {
  var str = "";
  str = this.replace("get_", "").titleize().replace(/ /g, "");
  if ( str == "Images" ) {
    return "Image";
  }
  if (this.substring(this.length - 3, this.length) == "ies") {
    str = str.substr(0, str.length - 3) + "y";
  } else if (this.substring(this.length - 2, this.length) == "es") {
    str = str.slice(0, -2);
  } else if (this.substring(this.length - 1, this.length) == "s" ) {
    str = str.slice(0, -1);
  }
  console.log("str is: " + str + " was " + this);
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
  displayRequestDebug(req);
  $.ajax({
    type: "POST",
    url: s.url,
    data: JSON.stringify(req),
    contentType: 'application/json',
    success: function(data) {
      if (callback)
        callback(data, options);
      displayResponseDebug(data);
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
  $('#results').html("");
  $('#arguments').html("");
  $('#messages').html("");
  var request;
  var div = $('#arguments');
  var tmpl_query = _.template($("#template_query_argument").html());
  var tmpl_query_select = _.template($("#template_query_argument_select").html());
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
          div.append( tmpl_query_select( query_args[i] ) );
        } else {
          div.append( tmpl_query( query_args[i] ) );
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

function displayRequestDebug( data ) {
  //var req_vars = ['action','proc','arguments','errors','warnings','notifications','status'];
  var div = $('#request_debug');
  div.html('');
  /*
  for ( var i = 0; i < req_vars.length; i++ ) {
    div.append('<h5>' + req_vars[i].titleize() + '</h5>');
    div.append('<pre>' + JSON.stringify(data[req_vars[i]],undefined,2) + '</pre>');
  }
  */
  div.html("<pre>" + JSON.stringify(data, null, "  ") + "</pre>");
  div.effect("highlight");
}

function displayResponseDebug( data ) {
  div = $('#response_debug');
  div.html('');
  div.html("<pre>" + JSON.stringify(data, null, "  ") + "</pre>");
  div.effect("highlight");
}

function displayAPIMethods( data ) {
  $('#methods').html('');
  $methods = data.payload;
  for (var key in data.payload ) {
    var button = $('<div class="small button">' + key.titleize() + '<div>');
    
    if ( key.indexOf('set_') == 0) {
      button.addClass('alert');
      button.on('click', function(k) {
        return function() {
          onSetMethodButtonClick(k);
        }
      }(key));
    }
    
    if ( key.indexOf('get_') == 0) {
      button.on('click', function(k) {
        return function() {
          onGetMethodButtonClick(k);
        }
      }(key));
    }
    
    $('#methods').prepend(button);
    $('#methods').prepend('<br />');
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

function renderResponseMessages(data) {
  var tmpl_message_response_rendered = "";
  var tmpl_message_rendered;
  
  tmpl_message_response_rendered += tmpl_message_response({
    messages: data.errors,
    severity: "error"
  });

  tmpl_message_response_rendered += tmpl_message_response({
    messages: data.warnings,
    severity: "warning"
  });

  tmpl_message_response_rendered += tmpl_message_response({
    messages: data.notifications,
    severity: "notification"
  });
  
  $("#messages").append(tmpl_message_response_rendered);
}

function displayModel(data) {
  $('#results').html('');
  
  renderResponseMessages(data);
  
  var statusmessage = "Received payload of length " + data.payload.length;
  tmpl_message_rendered = tmpl_message({
    message: statusmessage,
    severity: "success"
  });

  
  $("#messages").append(tmpl_message_rendered);
  
  json_path = [data.proc.replace("get_", "")];
  klass = data.proc.classify();
  if ( $supported_attributes[klass] ) {
    var html_result = renderEditFields(data.payload, data.proc.tableize(), 0, json_path);
    $("#results").html($("#results").html() + html_result);
    $(".datepicker").datepicker();
    $(".editme").on('change', onInputChanged);
  } else {
    var msg = "The method 'get_supported_attributes' does not define Klass '" + data.proc.classify() + "'. Inplace-editing of fields is therefore not supported. Simply displaying values instead.";
    $("#results").append(tmpl_message({
      message: statusmessage,
      severity: "notification"
    }));
    renderDisplayOnlyTable($("#results"), data.payload);
  }
}


/* SET FUNCTIONS */
function onSetMethodButtonClick(proc) {

  
  var json_path = [proc.replace("set_", "")];
  var table = proc.tableize();
  var klass = proc.tableize().classify();
  
  var tmpl_submit_button = _.template($("#template_submit_model_button").html());


  var row;
  var cols = '';
  var default_value;
  
  $("#messages").html("");
  $("#arguments").html("");
  
  $("#results").html(tmpl_submit_button({
    table: table,
  }));
  
  //console.log($supported_attributes[klass]);
  
  //if ($supported_attributes[klass] =
  $.each($supported_attributes[klass], function(key, desc) {
    if (desc.default) {
      // if get_supported_attributes specifies a default value, use that
      default_value = desc.default;
    } else {
      // else, invent our own
      if (desc.type == "array") {
        default_value = [];
      } else {
        default_value = "";
      }
    }
    
    tmpl_data = {
      columns: Math.floor(desc.sizehint * 22 / 10), 
      value: default_value,
      values: desc.values,
      key: key,
      type: desc.type,
      nested: false,
      json_path: table + "." + key,
      record_id: 0,
      klass: klass,
    };

    cols += tmpl_attr(tmpl_data);
    row = tmpl_attr_row({
      value: cols
    });
  });
 
  $("#results").append(row);
}

function onSubmitModelButtonClick(table) {
  var klass = table.classify();
  var request = prepareRequest();
  
  request.proc = "set_" + table;
  request.payload = [{}];
  
  $.each($supported_attributes[klass], function(key, desc) {
    
    /*
    if (key == "id") {
      return true;
    }
    */
    
    var element;
    var value;
    var json_path;
    
    element = $("[json_path='" + table + "." + key + "']");
    value = element.val();
    json_path = element.attr("json_path");
    
    console.log(key, value);
    
    if ( desc.type == "array" ) {
      value = value.split(",");
    }
    setDeepHashValue(request.payload[0], key, value);
  });
  getRequest(request, onSubmitModelComplete, {}); //xxx
}

function onSubmitModelComplete(data, options) {
  console.log("onSubmitModelComplete", data, options);
  renderResponseMessages(data);
}

/* ============================ */
/* HELPER FUNCTIONS */
/* ============================ */

function renderEditFields(collection, table, depth, json_path) {
  console.log("renderEditFields", collection, table, depth, json_path);
  var klass = table.classify();
  var html_result = '';
  var msg;
  
  /*
  // test if klass is specified
  if ( ! $supported_attributes[klass] ) {
    msg = "Klass '" + klass + "' not defined by proc 'get_supported_attributes'. Skipping.";
    $("#messages").append(tmpl_message({
      message: msg,
      severity: "warning"
    }));
    return;
  }
  */
  
  for (var i = 0; i < collection.length; i++) {
    var model = collection[i];
    var cols = '';
    var record_id = model.id;
    
    for (var key in model) {
        
      
      if (  reveal(model[key]) == "[object Array]" &&
            model[key].length > 0 &&
            reveal(model[key][0]) == "[object Object]"
         ) {
          // this is an array containing objects. display HAS_MANY relationship in place, via Recursion
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
          
      //} else if ( reveal(model[key]) == "[object Array]" ) {
        // loop over the array here, no recursion needed
        
      } else {
        // simple datatypes
        
        // test for length
        if ( reveal(model[key]) == "[object Array]" &&
             model[key].length == 0 &&
             typeof $supported_attributes[klass][key] == "undefined"
        ) {
          msg = "Attribute '" + key + "' in klass '" + klass + "' has zero length. Not rendering";
          cols += tmpl_message({
            message: msg,
            severity: "warning"
          });
          continue;
        }
        
        // test if key of klass is specified
        console.log(klass,key);
        if ( ! $supported_attributes[klass][key] ) {
          msg = "Attribute '" + key + "' in klass '" + klass + "' not defined by proc 'get_supported_attributes'. Skipping.";
          cols += tmpl_message({
            message: msg,
            severity: "warning"
          });
          continue;
        }
        
        var desc = $supported_attributes[klass][key];
        
        // test if actual value matches with the specifcations
        if ( 
          reveal(model[key]) != "[object " + desc.type.titleize() + "]" &&
          // exceptions to the previous rule. those are represented as strings on the JS side, so we allow them
          ! ( desc.type == "bool" ||
          desc.type == "number" ||
          desc.type == "date(y-m-d)" ||
          desc.type == "timestamp" ||
          desc.type == "text"
          )
        ) {
          msg = "ERROR! Key '" + key + "' of Klass '" + klass + "' is an " + reveal(model[key]) + ". The proc get_supported_attributes defined it as a " + desc.type.titleize();
          cols += tmpl_message({
            message: msg,
            severity: "error"
          });
          continue;
        }

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
  var klass = json_path[0].classify();
  var attribute = json_path[1];
  var desc = $supported_attributes[klass][attribute];
  
  //console.log("onInputChanged", input.val());
  var request = prepareRequest();
  request.proc = "set_" + json_path.shift();
  //json_path.shift();
  request.payload = [{
    id: input.attr("record_id"),
  }];
  

  var value = input.val();

   /* since arrays of values are not easily displayed as a form,
   * they are displayed as comma-separated lists. We have to
   * convert those back to an array
  */
  if ( desc.type == "array" ) {
    value = value.split(",");
  }
  setDeepHashValue(request.payload[0], json_path.join("."), value);
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
  var rendered_statusmessage = tmpl_message({
    messages: [msg],
    severity: "success"
  });
  $(rendered_statusmessage).insertAfter(options.element);
  
}


// this function doesn't make use of the underscore templating system. anyway it's useful for now
function renderDisplayOnlyTable(parent_element, collection) {
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
        renderDisplayOnlyTable(col, v);
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