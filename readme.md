ClickBlocks framework is using MVC architectural pattern.
MVC aims to separate business logic from user interface, so developers can easily modify individual parts of an application without affecting others. In the architecture of the MVC model provides data and rules of business logic, presentation of the user interface (eg, text, input field), and the controller provides the interface between model and view.
In addition, ClickBlocks also uses the front controller, which acts as the context of the URL-query. The front controller is processing the user request and sends it for further processing of subcontrollers.

Common questions:

When we want to use a new table in db, we must create some files in folder _engine/bll, _engine/collection, _engine/dal, _engine/orchestra (at least 4 files).
It takes time and sometimes we get mistakes.
Question: How can we generate these files by command?

You can use ORM class that contains all needed methods.
To generate new ORM classes generateClasses, to generate new db.xml generateXML.
The simplest way to do this is use Utility script:
To generate classes: http://YOUR_HOSTNAME/Utilities/orm/generate.php?mode=classes
To generate xml: http://YOUR_HOSTNAME/Utilities/orm/generate.php?mode=xml
