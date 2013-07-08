<style type="text/css">
  table.woocommerce-json-api-table {
    background: #222;
    -moz-box-shadow: 0 1px 3px rgba(0,0,0,0.5);
    -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.5);
    text-shadow: 0 -1px 1px rgba(0,0,0,0.25);
    border-bottom: 1px solid rgba(0,0,0,0.25);
    padding: 12px 10px 12px;
    cursor: pointer;
    color: white;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px;
    border-radius: 8px;
  }
  #json_api_page_id {
    width: 400px;
  }
  table.woocommerce-json-api-table label {
    font-weight: bolder;
    text-transform: uppercase;
  }
  table.woocommerce-json-api-table td {
    padding: 10px;
  }

</style>


<form method="post" id="mainform" action="" >
<?php echo $helpers->hiddenFormFields($helpers->getPluginPrefix() . '_sitewide_settings'); ?>
<table class="woocommerce-json-api-table" width="600px" align="center">
  <tr>
    <th colspan="2"><h3><?php echo $attrs['json_api_sitewide_settings']['title']; ?></h3></th>
  </tr>
<?php
  foreach ($attrs['json_api_sitewide_settings']['fields'] as $value) {
    ?>
      <tr>
        <td width="200px" valign="top">
          <?php echo $helpers->labelTag($value) ?>
        </td>
        <td>
         <?php 
            if ( $helpers->orEq($value,'type','') == 'text') { 
              echo $helpers->inputTag( $value ); 
            } else if ( $helpers->orEq($value,'type','') == 'textarea' ) {
              echo $helpers->textAreaTag( $value ); 
            } else if ( $helpers->orEq( $value, 'type', '') == 'select') {
              echo $helpers->selectTag( $value );
            }
         ?> 
        </td>
      </tr>
      <tr>
        <td colspan="2">
          <?php echo $value['description']?> <br />
          
        </td>
      </tr>
    <?php
  }
?>
  <tr>
    <td colspan="2" align="center">
      <input type="text" name="unused" value="<?php echo $helpers->getPermalinkBySlug($json_api_slug); ?>" class="widefat"/>
    </td>
  </tr>
  <tr>
    <td colspan="2" align="right">
      
      <input type="submit" name="submit" value="<?php _e('Save') ?>" />
    </td>
  </tr>
</table>
</form>
