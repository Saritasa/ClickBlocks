<?php

namespace ClickBlocks\Web\UI\POM;

use ClickBlocks\Core,
    ClickBlocks\Web,
    ClickBlocks\Web\UI\Helpers;

class CKEditor extends WebControl
{
   public function __construct($id, $text = null)
   {
      parent::__construct($id);
      $this->properties['options'] = array();
      $this->properties['text'] = $text;
      $this->attributes['name'] = $id;
   }

   public function &__get($param)
   {
      if ($param == 'options') return $this->properties['options'];
      return parent::__get($param);
   }

   public function clean()
   {
      $this->properties['text'] = null;
      return $this;
   }

   public function assign($value)
   {
      $this->properties['text'] = $value;
      return $this;
   }

   public function validate($type, Core\IDelegate $check)
   {
      return $check($this->properties['text']);
   }

   public function JS()
   {
      $this->js->addTool('ckeditor');
      if ($this->properties['visible'])
      {
         if (Web\Ajax::isAction()) $this->ajax->script($this->getConstructor(), $this->updated + 200, true);
         else $this->js->add(new Helpers\Script('ckeditorinit_' . $this->attributes['uniqueID'], $this->getConstructor()), 'foot');
      }
      return $this;
   }

   public function render()
   {
      if (!$this->properties['visible']) return $this->invisible();
      return '<span id="container_' . $this->attributes['uniqueID'] . '"><textarea' . $this->getParams() . '>' . $this->properties['text'] . '</textarea></span>';
   }

   protected function remove($time = 0)
   {
      $this->ajax->script($this->getDestructor(), $time, true);
      return parent::remove($time);
   }

   protected function repaint()
   {
      parent::repaint();
      $this->js->addTool('ckeditor');
      $this->ajax->script($this->getDestructor(), $this->updated, true);
      if ($this->properties['visible']) $this->ajax->script($this->getConstructor(), $this->updated + 200, true);
   }

   protected function getRepaintID()
   {
      return 'container_' . $this->attributes['uniqueID'];
   }

   private function getConstructor()
   {
      $options = (array)$this->properties['options'];
      $options = ($options) ? ', ' . json_encode($options) : '';
      return 'if (document.getElementById(\'' . $this->attributes['uniqueID'] . '\') && !CKEDITOR.instances.' . $this->attributes['uniqueID'] . ') CKEDITOR.replace(\'' . $this->attributes['uniqueID'] . '\'' . $options . ');';
   }

   private function getDestructor()
   {
      return 'if (CKEDITOR.instances.' . $this->attributes['uniqueID'] . ') delete(CKEDITOR.instances[\'' . $this->attributes['uniqueID'] . '\']);';
   }
}

?>