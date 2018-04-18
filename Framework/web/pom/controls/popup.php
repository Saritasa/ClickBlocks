<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\Core,
    ClickBlocks\MVC;

class Popup extends Panel
{
  protected $ctrl = 'popup';

  protected $dataAttributes = ['overlay' => 1, 'overlayclass' => 1, 'overlayselector' => 1, 'closebyescape' => 1, 'closebydocument' => 1, 'closebuttons' => 1];
  
  public function show($center = true, $delay = 0)
  {
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').show(' . ($center ? 'true' : 'false') . ')', $delay);
    return $this;
  }
  
  public function hide($delay = 0)
  {
    $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').hide()', $delay);
    return $this;
  }
}