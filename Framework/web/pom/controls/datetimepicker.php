<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\MVC;

class DateTimePicker extends Input
{
  protected $ctrl = 'datetimepicker';
  
  protected $dataAttributes = ['settings' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id, 'text', $value);
    $this->attributes['settings'] = [];
  }
  
  public function init()
  {
    $this->view->addCSS(['href' => \CB::url('framework') . '/web/js/jquery/datetimepicker/datetimepicker.css']);
    $this->view->addJS(['src' => \CB::url('framework') . '/web/js/jquery/datetimepicker/datetimepicker.js']);
    return $this;
  }
}