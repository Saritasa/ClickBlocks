<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\MVC;

class ColorPicker extends Input
{
  protected $ctrl = 'colorpicker';
  
  protected $dataAttributes = ['settings' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id, 'text', $value);
    $this->attributes['settings'] = ['showInput' => true, 'allowEmpty' => true, 'showInitial' => true, 'showAlpha' => true, 'preferredFormat' => 'rgb'];
  }
  
  public function init()
  {
    $this->view->addCSS(['href' => \CB::url('framework') . '/web/js/jquery/colorpicker/spectrum.css']);
    $this->view->addJS(['src' => \CB::url('framework') . '/web/js/jquery/colorpicker/spectrum.js']);
    return $this;
  }
}