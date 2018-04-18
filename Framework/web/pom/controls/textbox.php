<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\Core;

class TextBox extends Control
{
  const TYPE_TEXT = 'text';
  const TYPE_PASSWORD = 'password';
  const TYPE_EMAIL = 'email';
  const TYPE_MEMO = 'memo';

  protected $ctrl = 'textbox';
  
  protected $dataAttributes = ['default' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['type'] = self::TYPE_TEXT;
    $this->properties['value'] = $value;
  }
  
  public function &prop($property, $value = null)
  {
    if ($value === null)
    {
      $val = parent::prop($property);
      if (strtolower($property) == 'value' && strlen($val) == 0 && isset($this->attributes['default'])) $val = $this->attributes['default'];
      return $val;
    }
    return parent::prop($property, $value);
  }
  
  public function clean()
  {
    $this->properties['value'] = isset($this->attributes['default']) ? $this->attributes['default'] : '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check($this->prop('value'));
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    switch ($this->properties['type'])
    {
      case self::TYPE_MEMO:
        return '<textarea' . $this->renderAttributes() . '>' . $this->properties['value'] . '</textarea>';
      default:
        return '<input type="' . htmlspecialchars($this->properties['type']) . '"' . $this->renderAttributes() . ' value="' . htmlspecialchars($this->properties['value']) . '" />';
    }
  }
}