<?php

namespace ClickBlocks\Web\POM;

class Grid extends Panel
{
  protected $ctrl = 'grid';
  
  protected $count = null;
  
  protected $rows = null;
  
  public function __construct($id, $template = null)
  {
    parent::__construct($id, $template);
    $this->properties['source'] = null;
    $this->properties['sort'] = -1;
    $this->properties['size'] = 10;
    $this->properties['page'] = 0;
  }
  
  public function setSort($sort)
  {
    $this->properties['sort'] = $sort;
  }
  
  public function setSize($size)
  {
    $this->properties['size'] = $size;
  }
  
  public function setPage($page)
  {
    $this->properties['page'] = $page;
  }
  
  public function init()
  {
    foreach (['size', 'size1', 'size2'] as $id)
    {
      $ctrl = $this->get($id, false);
      if (!$ctrl) continue;
      if (!is_array($ctrl->options) || count($ctrl->options) == 0)
      {
        $ctrl->options = [10 => 10, 20 => 20, 30 => 30, 40 => 40, 50 => 50, 999999999 => 'All'];
      }
      unset($ctrl->multiple);
      $ctrl->value = $this->properties['size'];
      $ctrl->addEvent('changeSize', 'change', get_class($this) . '@' . $this->attributes['id'] . '->setSize', ['params' => ['js::this.value']]);
    }
    return $this;
  }
  
  public function getSortMethod($sort)
  {
    if (abs($this->properties['sort']) == $sort) $sort = $this->properties['sort'];
    return $this->method('setSort', [-$sort]);
  }
  
  public function getCount($cache = true)
  {
    if ($cache && $this->count !== null) return $this->count;
    if (is_array($this->properties['source'])) return $this->count = count($this->properties['source']);
    return $this->count = \CB::delegate($this->properties['source'], 'count', $this->properties);
  }
  
  public function getRows($cache = true)
  {
    if ($cache && $this->rows !== null) return $this->rows;
    if (is_array($this->properties['source']))
    {
      $tmp = $this->properties['source'];
      usort($tmp, [$this, 'cmp']);
      return $this->rows = array_slice($tmp, $this->properties['page'] * $this->properties['size'], $this->properties['size']);
    }
    return $this->rows = \CB::delegate($this->properties['source'], 'rows', $this->properties);
  }
  
  public function cmp($a, $b)
  {
    return 0;
  }
  
  public function renderInnerHTML()
  {
    if ($this->tpl->isExpired())
    {
      $this->tpl->count = $this->getCount();
      $this->normalize();
      $this->tpl->rows = $this->getRows();
      $this->tpl->page = $this->properties['page'];
      $this->tpl->sort = $this->properties['sort'];
      $from = $to = 0;
      if ($this->count)
      {
        $from = $this->properties['size'] * $this->properties['page'] + 1;
        $to = $from + $this->properties['size'] - 1;
        if ($to > $this->count) $to = $this->count;
      }
      $this->tpl->from = $from;
      $this->tpl->to = $to;
      foreach (['pagination', 'pagination1', 'pagination2'] as $id)
      {
        $ctrl = $this->get($id, false);
        if (!$ctrl) continue;
        $ctrl->callback = $this->method('setPage', ['#page#']);
        $ctrl->total = $this->count;
        $ctrl->size = $this->properties['size'];
        $ctrl->page = $this->properties['page'];
      }
    }
    return parent::renderInnerHTML();
  }
  
  public function normalize()
  {
    $this->properties['page'] = (int)$this->properties['page'];
    $this->properties['size'] = (int)$this->properties['size'];
    if ($this->properties['page'] < 0) $this->properties['page'] = 0;
    if ($this->properties['size'] < 1) $this->properties['size'] = 1;
    $last = ceil($this->count / $this->properties['size']) - 1;
    if ($last < 0) $last = 0;
    if ($this->properties['page'] > $last) $this->properties['page'] = $last;
  }
}