var ImageControl = function(el, pom)
{
  Image.superclass.constructor.call(this, el, pom);
};

$pom.registerControl('image', ImageControl);