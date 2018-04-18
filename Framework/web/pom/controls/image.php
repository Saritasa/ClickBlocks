<?php

namespace ClickBlocks\Web\POM;

class Image extends Control
{
  protected $ctrl = 'image';

  public function __construct($id)
  {
    parent::__construct($id);
    $this->properties['autorefresh'] = false;
    $this->properties['fitsize'] = false;
  }

  public function render()
  {
    if (!$this->properties['visible']) return $this->invisible();
    $src = $this->src;
    if (!empty($src))
    {
      if ($this->properties['fitsize'])
      {
        $image = \CB::dir($src);
        if (is_file($image))
        {
          list($w, $h, $type, $attr) = getimagesize($image);
          $this->setSize($this->width, $this->height, $w, $h);
        }
        $this->properties['fitsize'] = false;
      }
      if ($this->properties['autorefresh']) $this->attributes['src'] = $src . (strpos($src, '?') === false ? '?' : '&') . 'p' . rand(0, 1000000);
    }
    $html = '<img' . $this->renderAttributes() . ' />';
    if ($src !== null) $this->attributes['src'] = $src;
    return $html;
  }

  protected function setSize($width, $height, $w, $h)
  {
    $width = (int)$width;
    $height = (int)$height;
    if ($width == 0 && $height > 0)
    {
      $nh = $height;
      $nw = $height / $h * $w;
    }
    else if ($width > 0 && $height == 0)
    {
      $nw = $width;
      $nh = $width / $w * $h;
    }
    else if ($width > 0 && $height > 0)
    {
      $nh = $height;
      $nw = $height / $h * $w;
      if ($nw > $width)
      {
        $nh = $width / $nw * $nh;
        $nw = $width;
      }
    }
    else
    {
      $nw = $w;
      $nh = $h;
    }
    $this->attributes['width'] = ceil($nw);
    $this->attributes['height'] = ceil($nh);
  }
}