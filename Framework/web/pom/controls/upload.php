<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\MVC,
    ClickBlocks\Utils\PHP;

class Upload extends Control
{
  protected $ctrl = 'upload';
  
  protected $dataAttributes = ['settings' => 1, 'callback' => 1];
  
  public function init()
  {
    $url = \CB::url('framework-web');
    $this->view->addJS(['src' => $url . '/js/jquery/upload/vendor/jquery.ui.widget.js']);
    $this->view->addJS(['src' => $url . '/js/jquery/upload/jquery.iframe-transport.js']);
    $this->view->addJS(['src' => $url . '/js/jquery/upload/jquery.fileupload.js']);
    return $this;
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    return '<input type="file"' . $this->renderAttributes() . ' />';
  }
}