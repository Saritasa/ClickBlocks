<?php

namespace ClickBlocks\Web\POM;

class Radio extends Control
{
  protected $ctrl = 'radio';
  
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['align'] = 'right';
    $this->properties['tag'] = 'div';
    $this->properties['caption_class'] = null;
  }
  
  public function hasContainer()
  {
    return true;
  }
  
  public function check($flag = true)
  {
    if ($flag) $this->attributes['checked'] = 'checked';
    else unset($this->attributes['checked']);
    return $this;
  }
  
  public function clean()
  {
    unset($this->attributes['checked']);
    return $this;
  }

  public function validate(Validator $validator)
  {
    if ($validator instanceof VRequired) return $validator->check(!empty($this->attributes['checked']));
    return true;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<' . $this->properties['tag'] . ' id="' . self::CONTAINER_PREFIX . $this->attributes['id'] . '"' . $this->renderAttributes(false) . '>';
    if (strlen($this->properties['caption'])) $label = '<label for="' . $this->attributes['id'] . '" class="' . $this->properties['caption_class'] . '">' . $this->properties['caption'] . '</label>';
    if ($this->properties['align'] == 'left' && isset($label)) $html .= $label;
    $html .= '<input type="radio" value="' . htmlspecialchars($this->properties['value']) . '"' . $this->renderAttributes() . ' />';
    if ($this->properties['align'] != 'left' && isset($label)) $html .= $label;
    $html .= '</' . $this->properties['tag'] . '>';
    return $html;
  }
}