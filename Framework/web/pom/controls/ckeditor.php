<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\MVC,
    ClickBlocks\Utils\PHP;

class CKEditor extends Control
{
  protected $ctrl = 'ckeditor';
  
  protected $dataAttributes = ['settings' => 1];

  public function __construct($id, $value = null)
  {
    parent::__construct($id);
    $this->properties['value'] = $value;
  }
  
  public function init()
  {
    $this->view->addJS(['src' => \CB::url('framework') . '/web/js/ckeditor/ckeditor.js']);
    return $this;
  }
  
  public function clean()
  {
    $this->properties['value'] = '';
    return $this;
  }

  public function validate(Validator $validator)
  {
    return $validator->check($this->prop('value'));
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    unset($this->attributes['style']);
    return '<textarea' . $this->renderAttributes() . ' style="visibility:hidden;display:none;">' . $this->properties['value'] . '</textarea>';
  }
}