var VCustom = function(el, pom)
{
  VCustom.superclass.constructor.call(this, el, pom);
    
  this.validate = function()
  {
    var flag, validate = this.el.attr('data-clientfunction');
    if (validate)
    {
      this.setState(window[validate](this));
    }
    else if (this.el.attr('data-serverfunction'))
    {
      this.setState(true);
    }
    var ctrls = this.getControls();
    flag = this.el.attr('data-state') == '1';
    for (var i = 0; i < ctrls.length; i++) this.result[ctrls[i]] = flag;
    return flag;
  };
};

$pom.registerValidator('vcustom', VCustom);