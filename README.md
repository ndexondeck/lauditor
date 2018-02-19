# Lauditor

Lauditor is a laravel based auditing and authorization package, which helps you control your manage tasks, permissions and user groups. It is designed to manage all application tasks and user permission by utilization laravel's routes.

[![Build Status](https://travis-ci.org/myclabs/DeepCopy.png?branch=master)](https://travis-ci.org/myclabs/DeepCopy)
[![Coverage Status](https://coveralls.io/repos/myclabs/DeepCopy/badge.png?branch=master)](https://coveralls.io/r/myclabs/DeepCopy?branch=master)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/myclabs/DeepCopy/badges/quality-score.png?s=2747100c19b275f93a777e3297c6c12d1b68b934)](https://scrutinizer-ci.com/g/myclabs/DeepCopy/)
[![Total Downloads](https://poser.pugx.org/myclabs/deep-copy/downloads.svg)](https://packagist.org/packages/myclabs/deep-copy)


## Table of Contents

1. [How](#how)
    1. [Publish Vendor Files](#publish)
    1. [Audit](#audit)
    1. [Authorization](#authorization)
    1. [Generating Tasks](#task-generate)
    1. [Flushing Your DB](#db-flush)

## How?

Install with Composer:

```
composer require ndexondeck/lauditor
```

### Publish Vendor Files

```
php artisan vendor:publish --tag=ndexondeck-lauditor
```

After this please uncomment the name spaces in the following files

app/Ndexondeck/Lauditor/Util.php
```
//namespace App\Ndexondeck\Lauditor;
```
TO
```
namespace App\Ndexondeck\Lauditor;
```

Similar, do the same for
1) app/BaseModel.php
1) app/Group.php
1) app/Login.php
1) app/Module.php
1) app/Permission.php
1) app/PermissionAuthorizer.php
1) app/Staff.php
1) app/Task.php

=> Note that all these models will be copied from the library, to you app folder, you can do away with or modify then where necessary

=> Furthermore, there are certain methods in the Util class that needs to be updated



### Auditing

```php
Use Ndexondeck\Lauditor\Model\Audit;

class YourAuditModel extends Audit {

}
```

### Authorization

```php
Use Ndexondeck\Lauditor\Model\Authorization;

class YourAuthorizedModel extends Authorization {

}
```

### Generating Tasks

This feature works with your routes, where unique route naming is ensured

`php artisan task:genrate`

### Flushing Your Database

This feature can help you flush your database, even multiple database simultaneously. See help for more

`php artisan db:flush`

### Request and Response for APPLICATION API

If you would like to document additional information like the request and response of all your API's, you can add "\Ndexondeck\Lauditor\Middleware\LogAfterRequest::class" the Http/Kernel.php file
as shown below.

```php
    //...

    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        \Ndexondeck\Lauditor\Middleware\LogAfterRequest::class
    ];
    
    //...
```



## Audits (How it Works)

- The audit trail approach is a background process, which occurs at a Model level, any change to a model can be detected and then audited if the model is a child of Audit model.

Consider this: We want to keep audit trails of Staff Model below.

![staff_table](doc/staff.png)

```php
Class Staff extends Audit{

    function boot(){
        parent::boot();
    }
}
```
The Audit model binds the creating, updating and deleting event listeners to the Staff model, through which audit trails can be captured.

![audit_table](doc/audit.png)
```
●	id - primary key
●	login_id - the Login id of the logged in user foreign key
●	trail_type - the base class name of the trailed model e.g App\Staff
●	traild_id - the id of the record in the trailed table
●	authorization_id - present when a trail was authorized before committing
●	user_action - a customizable name given to the user’s action that led to the trail
●	table_name - the name of the trailed table
●	action - the database action taken on the trail (create, update or delete)
●	ip - the IP address of the user who initiated this action
●	rid - the request identification hash aka the commit id
●	status - determines the type and state of an audit
    ○	0 - An audit in revoked state
    ○	1 - An audit in active state
    ○	2 - An audit log i.e logs of audit events
    ○	3 - An audit awaiting authorization (pending trail)
●	before - a json value that keeps the trail’s state before an action
●	after - a json value that keeps the trail’s state after an action
●	dependency - present when a set of pending audit trails depends on the execution results of its predecessor when authorized
    e,g suppose we have the following trails waiting authorization in the following order. Create Staff, Create Login
    the Login->staff_id property may depend on the of the Staff->id
    Login::setDependency([
        ‘staff_id’ => ‘staff.id’ 
    ]);
    The method above will Add a dependency for the Create Login trail, to indicate that Login->staff_id will be derived from Staff->id
●	created_at - this will indicate the time the audit record was created.
●	updated_at - this will indicate when there was a last status change to an audit.
```

- Now lets get to see the available methods

...still loading

...meanwhile thanks to Adekunle Adekoya (Crystoline) for Helping out with testing