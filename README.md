# c5_base_model
A base model with some helpful functionality for interacting with database records in concrete5 7+

basic usage:

```php

use TripleI\C5BaseModel\BaseModel

class Widget extends BaseModel
{
    /**
     * @var string
     * @Column(type="string", nullable=true)
     */
    protected $name;
    
    /**
     * @var string
     * @Column(type="text", nullable=true)
     */
    protected $description;
}

$widget = new Widget();
$widget->name = 'testing';
$widget->save();

$widget2 = new Widget();
$widget->setData(
    [
        'name' => 'widget name',
        'description' => 'description here'
    ]
);
$widget->save();

$allWidgets = Widget::getAll();

$oddWidgets = Widget::loadByIDs([1, 3, 5]);

$singleWidget = Widget::getByID(1);
$singleWidget->destroy();

```
