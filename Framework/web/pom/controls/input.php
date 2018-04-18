<?php

namespace ClickBlocks\Web\POM;

class Input extends Control
{
  protected $ctrl = 'input';
  
  public function __construct($id, $type = 'text', $value = null)
  {
    parent::__construct($id);
    $this->attributes['type'] = $type;
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
    return '<input value="' . htmlspecialchars($this->properties['value']) . '"' . $this->renderAttributes() . ' />';
  }
}