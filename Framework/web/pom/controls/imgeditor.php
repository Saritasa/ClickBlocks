<?php

namespace ClickBlocks\Web\POM;

use ClickBlocks\Core,
    ClickBlocks\MVC,
    ClickBlocks\Utils;

class ImgEditor extends Popup
{
  /**
   * Error message templates.
   */
  const ERR_IMGEDITOR_1 = 'Control with ID "[{var}]" is not found.';
  const ERR_IMGEDITOR_2 = 'Control with ID "[{var}]" is not an upload control.';

  protected $ctrl = 'imgeditor';
  
  public function __construct($id, $template = null, $expire = 0)
  {
    parent::__construct($id, $template ?: \CB::dir('framework') . '/web/js/imgeditor/imgeditor.html', $expire);
    $this->attributes['class'] = 'imgeditor';
  }
  
  public function init()
  {
    $this->view->addCSS(['href' => \CB::url('framework') . '/web/js/imgeditor/imgeditor.css']);
    $this->view->addJS(['src' => \CB::url('framework') . '/web/js/imgeditor/raphael-min.js']);
    $this->tpl->id = $id = $this->attributes['id'];
    $this->get('rotate')->settings = ['start' => 0, 
                                      'connect' => 'lower', 
                                      'range' => ['min' => -180, 'max' => 180], 
                                      'serialization' => ['lower' => ['js::$.Link({target: $("#angle_' . $id . '")})']]];
    $this->get('zoom')->settings = ['start' => 100,
                                    'step' => 1,
                                    'connect' => 'lower', 
                                    'range' => ['min' => 1, 'max' => 1000], 
                                    'serialization' => ['lower' => ['js::$.Link({target: $("#scale_' . $id . '")})']]];
    return $this;
  }
  
  public function setup(array $uploads)
  {
    $tmp = [];
    foreach ($uploads as $id => $data)
    {
      $ctrl = $this->view->get($id);
      if (!$ctrl) throw new Core\Exception($this, 'ERR_IMGEDITOR_1', $id);
      if (!($ctrl instanceof Upload)) throw new Core\Exception($this, 'ERR_IMGEDITOR_2', $id);
      $ctrl->multiple = false;
      $ctrl->settings['singleFileUploads'] = true;
      $ctrl->callback = stripslashes($this->callback('upload'));
      $tmp[$ctrl->attr('id')] = $data;
    }
    $this->tpl->uploads = $tmp;
  }
  
  public function info($id)
  {
    
  }
  
  public function upload($uniqueID)
  {
    $data = $this->tpl->uploads[$uniqueID];
    $file = new Utils\UploadedFile($_FILES[$uniqueID]);
    $file->unique = true;
    $file->destination = isset($data['destination']) ? $data['destination'] : \CB::dir('temp');
    $file->extensions = isset($data['extensions']) ? $data['extensions'] : ['png', 'jpg', 'jpeg', 'gif'];
    $file->types = isset($data['types']) ? $data['types'] : [];
    $file->max = isset($data['maxSize']) ? $data['maxSize'] : null;
    if (false !== $info = $file->move())
    {
      //$this->get('btnAply')->onclick = $this->method('apply', [$uniqueID]);
      $this->view->action('$pom.get(\'' . $this->attributes['id'] . '\').show(true)');
      //if (isset($data['onSuccess'])) \CB::delegate($data['onSuccess'], $info);
      //if (isset($data['onComplete'])) \CB::delegate($data['onComplete'], $info);
    }
    else
    {
      $error = $file->getErrorMessage();
      if (isset($data['onError'])) \CB::delegate($data['onError'], $uniqueID, $error);
      else $this->view->action('alert', 'Error! ' . $error);
      //if (isset($data['onComplete'])) \CB::delegate($data['onComplete'], $error);
    }
  }
}
