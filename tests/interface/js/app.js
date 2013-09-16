String.prototype.titleize = function () {
    return this.replace(/_/g," ").replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
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
  for (var i = 0; i < data.payload.length; i++ ) {
    var button = $('<div class="small button">' + data.payload[i].titleize() + '<div>');
    if ( data.payload[i].indexOf('set_') == 0) {
      button.addClass('alert');
    }
    var method = data.payload[i].titleize();
    method = 'on' + method.replace(/\s/g,'') + "ButtonClick";
    console.log("Method would be: " + method);
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
function displayProducts( data ) {
  var products = data.payload;
  $('#results').html('');
  var tmpl = _.template( $('#product_row_template').html() );
  for ( var i = 0; i < products.length; i++ ) {
    var product = products[i];
    $('#results').append( tmpl( product ) );
  }
}
function displayCategories( data ) {
  console.log("Called");
  var cats = data.payload;
  $('#results').html('');
  var tmpl = _.template( $('#category_row_template').html() );
  for ( var i = 0; i < cats.length; i++ ) {
    var cat = cats[i];
    $('#results').append( tmpl( cat ) );
  }
}
function onGetRequestComplete( data, status, xhr ) {
  switch( data.proc ) {
    case 'get_api_methods':
      displayAPIMethods(data);
      break;
    case 'get_system_time':
      displaySystemTime(data);
      break;
    case 'get_products':
      displayProducts( data );
      break;
    case 'set_products':
      displayProducts( data );
      break;
    case 'get_categories':
      displayCategories( data );
      break;
  }
  displayDebug(data);
}
function getRequest( req ) {
  var s = getSettings();
  //$.getJSON(s.url, req, onGetRequestComplete );
  $.ajax({
    type: "POST",
    url: s.url,
    data: JSON.stringify(req),
    contentType: 'application/json',
    success: onGetRequestComplete,
    dataType: 'json',
    error: function (xhr,type) { console.log( "ERROR", xhr, type)},
  });
  console.log("Sent", req);
}
function onLoadMethodsButtonClick() {
  var request = prepareRequest('get_api_methods');
  getRequest( request );
}
function onGetSystemTimeButtonClick() {
  var request = prepareRequest('get_system_time');
  getRequest( request );
}
function onGetCategoriesButtonClick() {
  var request = prepareRequest('get_categories');
  getRequest( request );
}
function onGetProductsButtonClick() {
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var args = [
    { 
      columns: 12,
      id: "order_by",
      label:"Order By", 
      placeholder: ['ID','post_title','post_date','post_author','post_modified'].join("|")
    },
    { 
      columns: 3,
      id: "page",
      label:"Page", 
      placeholder: 1,
    },
    { 
      columns: 3,
      id: "per_page",
      label:"Per Page", 
      placeholder: 5,
    },
    { 
      columns: 12,
      id: "ids",
      label:"Ids Filter", 
      placeholder: 'Comma separated list of ids',
    },
    { 
      columns: 12,
      id: "parent_ids",
      label:"Parent Ids Filter", 
      placeholder: 'Comma separated list of parent ids',
    },
    { 
      columns: 12,
      id: "skus",
      label:"SKUs Filter", 
      placeholder: 'Comma separated list of product SKUS',
    },
  ];
  for ( var i = 0; i < args.length; i++ ) {
    div.append( tmpl( args[i] ) );
  }
  var button = $('<div class="small button">Load Products</div>');
  button.on('click', function () {
    var request = prepareRequest('get_products');
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if (typeof arg.placehold == 'string' && arg.placeholder.indexOf('Comma separated') != -1) {
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
function onGetCategoriesButtonClick() {
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var args = [
    { 
      columns: 12,
      id: "order_by",
      label:"Order By", 
      placeholder: ['ID','count','name'].join("|")
    },
    { 
      columns: 3,
      id: "page",
      label:"Page", 
      placeholder: 1,
    },
    { 
      columns: 3,
      id: "per_page",
      label:"Per Page", 
      placeholder: 5,
    },
    { 
      columns: 12,
      id: "ids",
      label:"Ids Filter", 
      placeholder: 'Comma separated list of ids',
    },
    { 
      columns: 12,
      id: "parent_ids",
      label:"Parent Ids Filter", 
      placeholder: 'Comma separated list of parent ids',
    },
  ];
  for ( var i = 0; i < args.length; i++ ) {
    div.append( tmpl( args[i] ) );
  }
  var button = $('<div class="small button">Load Categories</div>');
  button.on('click', function () {
    var request = prepareRequest('get_categories');
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if (typeof arg.placehold == 'string' && arg.placeholder.indexOf('Comma separated') != -1) {
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
function onSetProductsButtonClick() {
  var div = $('#arguments');
  div.html('');
  var tmpl = _.template($('#argument_template').html());
  var select_tmpl = _.template($('#argument_template_select').html());
  var args = [
    { 
      columns: 12,
      id: "name",
      label:"Name", 
      placeholder: ''
    },
    { 
      columns: 5,
      id: "price",
      label:"Price", 
      placeholder: ''
    },
    { 
      columns: 7,
      id: "sku",
      label:"SKU", 
      placeholder: ''
    },
    { 
      columns: 3,
      id: "type",
      label:"Type",
      options: ['product','product_variation'], 
      placeholder: ''
    },
    { 
      columns: 3,
      id: "publishing",
      label:"Publishing",
      options: ['publish','draft','private','future'], 
      placeholder: ''
    },
    
  ];
  for ( var i = 0; i < args.length; i++ ) {
    if ( args[i].options ) {
      div.append( select_tmpl( args[i] ) );
    } else {
      div.append( tmpl( args[i] ) );
    }
    
  }
  var button = $('<div class="small button">Create Product</div>');
  button.on('click', function () {
    var request = prepareRequest('set_products');
    var obj = {};
    for ( var i = 0; i < args.length; i++ ) {
      var arg = args[i];
      var val;

      if (typeof arg.placehold == 'string' && arg.placeholder.indexOf('Comma separated') != -1) {
        val = $('#' + arg.id).val();
        val = val.split(',');
      } else {
        val = $('#' + arg.id).val();
      }
      obj[arg.id] = val;
    }
    request.payload = [obj];
    getRequest( request );
  });
  div.append("<hr />")
  div.append(button);
}
function onGetSupportedAttributesButtonClick() {
  var request = prepareRequest('get_supported_attributes');
  request.arguments.resources = ['Product','Order','Category','Customer','OrderItem'];
  getRequest(request);
}
$(function () {
  $('#load_methods_button').on('click',onLoadMethodsButtonClick);
});