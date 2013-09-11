(function ($) {
  var ControlPanel;
  $.fn.control_panel = function (options) {
    var opts = $.extend({},$.fn.control_panel.defaults, options);
    return this.each(function () {
      var $this = this;
      $this.cp = new ControlPanel( $this );
    });
  };
  $.fn.control_panel.defaults = {};
  ControlPanel = function ( target_element) {
    var self = this;
    this.pAttributes = []; // Will be an array of objects
    this.pInputs = []; // will be an array of objects where element is a jQuery object
    this.pTarget = target_element; // the DOM element
    this.pControls = []; // Array of buttons
    this.pPanelWidth = 0;
    this.add_attribute = function ( obj ) {
      self.pAttributes.push( obj );
      self.sort_attributes();
    }
    this.sort_attributes = function () {
      self.pAttributes.sort(function (a,b) {
        if ( a.order < b.order ) {
          return -1;
        } else if ( a.order > b.order ) {
          return 1;
        } else {
          return 0;
        }
      });
    }

    this.add_control = function ( obj ) {
      self.pControls.push( obj );
      self.sort_controls();
    }
    this.sort_controls = function () {
      self.pControls.sort(function (a,b) {
        if ( a.order < b.order ) {
          return -1;
        } else if ( a.order > b.order ) {
          return 1;
        } else {
          return 0;
        }
      });
    }

    this.draw = function () {
      var panel = $(self.pTarget);
      var i;
      self.pPanelWidth = panel.width();
      panel.html('');
      panel.css({ 'margin-bottom': '20px'});

      for ( i = 0; i < self.pAttributes.length; i++ ) {
        var elem = self.pInputs[i];
        var obj = self.pAttributes[i];
        if ( ! elem ) {

          elem = self.new_element( obj );
          self.pInputs[i] = elem;
        } 
        if ( i % 3 == 0 ) {
          //panel.append('<br style="clear:both;"/>');
        }
        panel.append(elem); 
        if ( obj.hidden === true ) {
          elem.hide();
        }
      }
      panel.append('<br style="clear:both;"/>');
      panel.append( self.new_controls() );
    }
    this.new_element = function ( obj ) {
      var div = $('<div id="control_panel_div_for_' + obj.id + '" class="control-panel-element"></div>');
      var label = $('<label id="control_panel_label_for' + obj.id + '"></label>');
      label.html(obj.label);
      label.css({'font-weight': 'bolder'});
      var input;
      if ( obj.type == 'string' ) {
        input = $('<input type="text" id="' + obj.id + '" class="control-panel-input" value="' + obj.default_value + '"/>')
        if ( obj.size ) {
          input.attr('size',obj.size);
        }
      } else if ( obj.type == 'select' ) {
        input = $('<select id="' + obj.id + '" class="control-panel-select" ></select>');
        for (var i = 0; i < obj.options.length; i++ ) {
          var opt = obj.options[i];
          var selected = false;
          if ( opt.indexOf(':') != -1 ) {
            parts = opt.split(":");
          } else {
            parts = [opt,opt];
          }
          if ( parts[1] == 'selected') {
            parts[1] = parts[0];
          }
          var option = $('<option value="' + parts[0] + '">' + parts[1] + '</option>');
          if ( opt.indexOf(':selected') != -1 ) {
            option.attr('selected',true);
          }
          input.append(option);
        } 
      }
      div.append(label);
      div.append("<br />");
      div.append(input);
      for ( var cb in obj.callbacks ) {
        input.on( cb,obj.callbacks[cb] );
        if ( obj.affects && obj.affects[cb] ) {
          for ( var i = 0; i < obj.affects[cb].length; i++ ) {
            var id = obj.affects[cb][i];
            input.bind(cb, (function (id,cb) {
              return function () {
                $('#'+id).trigger(cb);
              };
            })(id,cb) );
          }
        }
      }

      var width = Math.ceil( self.pPanelWidth / 3 ) - 45;
     //console.log("Width:",width);
      div.css(
        {
          float: 'left',
          border: '1px solid #3f4c6b',
          padding: '5px',
          margin: '5px',
          'margin-right': '10px',
          'border-radius': '5px',
          background: 'linear-gradient(to bottom, #f2f5f6 0%,#e3eaed 37%,#c8d7dc 100%)', 

        }
      );
      input.css({
        background: ' linear-gradient(to bottom, #ffffff 0%,#e5e5e5 61%)',
        border: "1px solid #3f4c6b",
        padding: '3px',
        'padding-left': '5px',
        'font-size': '1em',
        'border-radius': '3px',
      });
      div.width(width);

      return div;
    }

    this.new_controls = function () {
      var div = $('<div id="control_panel_controls" class="control-panel-controls"></div>');
      for ( var i = 0; i < self.pControls.length; i++) {
        var ctrl = self.pControls[i];
        var elem = $('<button id="' + ctrl.id + '">' + ctrl.label + '</button>');
        for ( var cb in ctrl.callbacks ) {
          elem.on( cb, ctrl.callbacks[cb] );
        }
        div.append(elem);
      }
      return div;
    }

    this.display_debug_info = function () {
      console.log("Attributes", self.pAttributes);
      console.log("Controls", self.pControls);
      console.log("Inputs", self.pInputs);
      console.log("Target", self.pTarget);
    }
    this.display_attribute_template = function () {
      console.log({
        id: 'id_of_attr',
        label: 'Label Text',
        order: 0,
        min_value: 0,
        max_value: 10,
        step_value: 1,
        type: 'string|text|select',
        options: ['value:Label'],
        affects: [
          'id_of_another_attr'
        ],
        hides: [
          'id_of_another_attr'
        ],
        callbacks: {
          change: function () {},
          click: function () {}
        }
      });
    }
  }
})(jQuery);