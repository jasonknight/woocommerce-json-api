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
function onGetRequestComplete( data, status, xhr ) {
  console.log( data,status,xhr );
}
function getRequest( req ) {
  var s = getSettings();
  $.get(s.url, req, onGetRequestComplete );
  console.log("Sent");
}
function onLoadMethodsButtonClick() {
  var request = prepareRequest('get_api_methods');
  getRequest( request );
}
$(function () {
  $('#load_methods_button').on('click',onLoadMethodsButtonClick);
});