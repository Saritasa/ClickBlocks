<?php

namespace ClickBlocks\Web\POM;

class Hidden extends Control
{
  protected $ctrl = 'hidden';
  
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
  }
  
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check($this->properties['value']);
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<input type="hidden" value="' . htmlspecialchars($this->properties['value']) . '"' . $this->renderAttributes() . ' />';
  }
}