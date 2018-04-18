<?php

namespace ClickBlocks\Web\POM;

class DropDownBox extends Control
{
  protected $ctrl = 'dropdownbox';
  
  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
    $this->properties['caption'] = null;
    $this->properties['options'] = [];
    $this->attributes['disabled'] = null;
  }
  
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check(is_array($this->properties['value']) ? implode('', $this->properties['value']) : $this->properties['value']);
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $html = '<select' . $this->renderAttributes() . '>';
    if (strlen($this->properties['caption'])) $html .= $this->getOptionHTML('', $this->properties['caption']);
    foreach ((array)$this->properties['options'] as $key => $value)
    {
      if (is_array($value))
      {
        $html .= '<optgroup label="' . htmlspecialchars($key) . '">';
        foreach ($value as $k => $v) $html .= $this->getOptionHTML($k, $v);
        $html .= '</optgroup>';
      }
      else
      {
        $html .= $this->getOptionHTML($key, $value);
      }
    }
    $html .= '</select>';
    return $html;
  }
  
  protected function getOptionHTML($value, $text)
  {
    if (is_array($this->properties['value'])) 
    {
      $s = in_array($value, $this->properties['value']) ? ' selected="selected"' : '';
    }
    else
    {
      $s = (string)$this->properties['value'] === (string)$value ? ' selected="selected"' : '';
    }
    return '<option value="' . htmlspecialchars($value) . '"' . $s . '>' . htmlspecialchars($text) . '</option>';
  }
}