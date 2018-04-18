<?php

namespace ClickBlocks\Web\POM;

class Button extends Control
{
  protected $ctrl = 'button';

  protected $dataAttributes = ['toggle' => 1, 'target' => 1];
  
  public function __construct($id, $text = null)
  {
    parent::__construct($id);
    $this->attributes['type'] = 'button';
    $this->properties['text'] = $text;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<button' . $this->renderAttributes() . '>' . $this->properties['text'] . '</button>';
  }
}