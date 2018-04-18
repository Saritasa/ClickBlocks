<?php

namespace ClickBlocks\Web\POM;

class HyperLink extends Control
{
  protected $ctrl = 'hyperlink';
  
  public function __construct($id, $href = null, $text = null)
  {
    parent::__construct($id);
    if (strlen($href)) $this->attributes['href'] = $href;
    $this->properties['text'] = $text;
  }
  
  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<a' . $this->renderAttributes() . '>' . $this->properties['text'] . '</a>';
  }
}