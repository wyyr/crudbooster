# Select Form Type
Showing a combo box (Spinner) input type

### Code Sample With Your Own Enum
```php
$this->form[] = ['label' => 'Platform', 'name' => 'platform', 'type' => 'select', 'dataenum' => 'Android;Ios;Website'];
```
#### Or you can make different value and label with pipe symbol
Use `value|LabelForOption` to your dataenum
```php
$this->form[] = ['label' => 'Platform', 'name' => 'platform', 'type' => 'select', 'dataenum' => 'android|Android;ios|Ios;website|Website'];
```

### Looking Up To A Table
#### categories Table
| Field Name | Data Type |
| ---------- | --------- |
| id | int(PK) |
| created_at | timestamp | 
| name | varchar(255) |

#### Code Sample
```php
$this->form[] = ['label'=>'Category','name'=>'categories_id','type'=>'select','datatable'=>'categories,name'];
```
in datatable attribute, first value is a table name, and the second value is a field wich you want to show as option label

### Make A Condition to the Query
```php
$this->form[] = ['label'=>'Category','name'=>'categories_id','type'=>'select','datatable'=>'categories,name','datatable_where'=>'id != 3'];
```
Add `datatable_where` attribute and fill it with `where` query like in sql.

### Customize the label of option
```php
$this->form[] = ['label'=>'Category','name'=>'categories_id','type'=>'select','datatable'=>'categories,name','datatable_format'=>"id,' - ',name"];
```
Add `datatable_format` attribute and fill it with a format. Specify the field name wich you want to show. You may specify the field more than one. Sparate it with a comma. If there is a word in addition to the field name, you must put a single quote in the prefix and suffix to its word. (See the example).

## What's Next
- [Form Input Type: select2](./form-select2.md)

## Table Of Contents
- [Back To Index](./index.md)