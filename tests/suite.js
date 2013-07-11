$ = jQuery;
var params = {
  action: 'woocommerce_json_api',
  arguments: {
    token: 1234,
  }
};
var url = 'http://woo.localhost/c6db13944977ac5f7a8305bbfb06fd6a/?callback=?';

function inspect_api_result(data) {
  for ( var i in data.payload) {
    if ( data.proc.indexOf("get_products") != -1) {
      console.log(data.payload[i].name + " " + data.payload[i].price + " " + data.payload[i].publishing, data.payload[i]);
    }
    
  }
}

function get_products(page,per) {
  params.proc = "get_products";
  params.arguments.page = page;
  params.arguments.per_page = per;
  $.getJSON(url, params, inspect_api_result);
}
function get_products_by_tags(tags,page,per) {
  params.proc = "get_products_by_tags";
  params.arguments.page = page;
  params.arguments.per_page = per;
  params.arguments.tags = tags;
  $.getJSON(url, params, inspect_api_result);
}