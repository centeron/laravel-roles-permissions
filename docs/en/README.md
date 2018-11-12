# Roles and Permissions for Laravel (RBAC)

* [Description](#description)
* [Installation](#installation)
* [Usage](#usage)
  * [Creating, deleting, inheriting roles and permissions](#creating-deleting-inheriting-roles-and-permissions)
  * [Attaching roles and permissions to users](#attaching-roles-and-permissions-to-users)
  * [Using via trait](#using-via-trait)
  * [Using via gate](#using-via-gate)
  * [Using via middleware](#using-via-middleware)
  * [Using via Blade directives](#using-via-blade-directives)
  * [Artisan commands](#artisan-commands)
* [Advanced usage of permissions](#advanced-usage-of-permissions)
* [Cache](#cache)
* [Database structure](#database-structure)

## Description
Laravel has Access Control List (ACL) out of the box to work with user permissions. However, if you need to build a flexible
access system based on roles and permissions with an inheritance, take into account their trigger logic. Futhermore, saving all
of that in a database the basic functionallity will not be enough. The current laravel package is a simple powerful instument which allows
development of both regular RBAC and the most complicated systems via simple integration within your application. 

### What does this package support?

- Multiple user models
- Infinite inheritance for roles and permissions (with checking of infinite looping)
- Multiple roles and permissions for users 
- Expandability of auth items (roles, permissions, ...) in cases of separation them by guard or something else
- Setting the logic of permission triggering (see [Advanced usage](#advanced-usage-of-permissions))
- Checking roles and permissions by their given names, id or class instances
- Rights checking via trait, gate, middleware, blade directives 
- Ability to enable and disable the cache
- Artisan-commands for working with RBAC

## Installation

You can install the package via composer:
```bash
composer require centeron/laravel-roles-permissions
```
You can publish the config file with:
```bash
php artisan vendor:publish --provider="Centeron\Permissions\ServiceProvider" --tag="config"
```
After the command has been executed you can change database table names in `config/permissions.php` file.
You also can change cache settings.

Publish the migration with:
```bash
php artisan vendor:publish --provider="Centeron\Permissions\ServiceProvider" --tag="migrations"
```
And perform the migration:
```bash
php artisan migrate
```

## Usage

First of all add the trait `Centeron\Permissions\Traits\HasAuthItems` to your model `User`

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Centeron\Permissions\Traits\HasAuthItems;

class User extends Authenticatable
{
    use HasAuthItems;

    // ...
}
```

Now `User` can add roles and permissions, check ability for access, etc. Every user can attach multiple roles and permissions.
Permissions can be attached to roles, models and other parent permissions. The same opportunities are available for roles.
In fact there is no real difference between role and permission. It is merely a formal devision of authoriztion items (auth-items) for logic types.
Feel free to add as much new types of auth-items as you need in your laravel application. 

### Creating, deleting, inheriting roles and permissions

To add new role or permission:
```php
use Centeron\Permissions\Models\AuthItem;

$role = AuthItem::createRole(['name' => 'admin']);
$role = AuthItem::create(['name' => 'admin', 'type' => AuthItem::TYPE_ROLE]); // alternative to the previous code

$permission = AuthItem::createPermission(['name' => 'admin']);
$permission = AuthItem::create(['name' => 'admin', 'type' => AuthItem::TYPE_PERMISSION]); // alternative to the previous code

```
`AuthItem` is an extended Eloquent model. So you can use any Eloquent-command you need including deleting:

```php
use Centeron\Permissions\Models\AuthItem;

AuthItem::where('name', 'admin')->delete();
```

Using `addChilds` and `addParents` you can add parent and child items, organizing hierarchy of any depth you require:

```php
$role->addChilds($permission1, $permission2, $subRole);
$permission2->addChilds($permission2_1);
$permission2_1_1->addParents($permission2_1);
```

Similarly `removeChilds` and `removeParents` remove these relations (without deleting entitities `AuthItem` themselves).

In the example above the variables are instances of `AuthItem`. However you can use names of roles and permissions or even their ID
as parameters. Method recognizes how to work with these parameteres by parameter`s types (int/string/class). I.e.
the next code will work correctly as well:

```php
$permission = AuthItem::where('name', 'Create post')->first();

$role->addChilds($permission, 'Update post', 4);
$role->removeParents($permission, 4);
```

You can check whether the item has rights of other items (in other words whether is a child in any generation) with the fucntion
`hasAny` (has any of the given items) and `hasAll`(has all of the given items). Of course, these methods are equal if has been
given only one parameter.

```php
$hasAny = $role->hasAny('Update post', 4); // true, because permission 'Update post' presents
$hasAll = $role->hasAll('Update post', 4); // false, because permission with ID=4 absents. 
```

### Attaching roles and permissions to users

Empowering models can occur as from model side using a trait. `Centeron\Permissions\Traits\HasAuthItems`:

```php
$user->attachAuthItems('admin', $otherRoleModel, 'Create post', 4); 
```

as from roles/permission`s side giving assigning models as parameters to them:

```php
$otherRoleModel->attach($user)
```
A permission (role) can be detached from a user using `detachAuthItems`:

```php
$user->detachAuthItems('Create post'); 
```

### Using via trait

You can determine if a user has roles and permissions:

```php
$user->hasAnyAuthItems('View post', 'Edit post'); // true, if it has any of a given list
$user->hasAllAuthItems('View post', 'Edit post'); // true, if it has all of a given list
```

But there can be a case when users should only edit own posts, or only if they have approptiate rating for that.
In such cases we have to use other functions:

```php
$user->canAnyAuthItems(['View post', 'Edit post'], [1]); // true, possible edit or view a post with ID = 1
$user->hasAllAuthItems(['View post', 'Edit post'], [1]); // true, possible edit and view post with ID = 1
```
More about conditional access in chapter [Advanced usage](#advanced-usage-of-permissions)

### Using via gate

The standard way to check rights using facade `Gate` also works. You still may use methods
`check`, `allow` and `denies` of `Gate`:

```php
      if (Gate::denies('Edit post', $post)) {}
      if (Gate::allows('Delete comment', [$post, $comment])) {}
      if (Gate::check('admin')) {}      
```

You do not need to use method `define`. All definitions are housed in the database. Rights inheritance is taken into account.

In controllers you may use helper as well:

```php
    $this->authorize('Edit post', $post);
```

### Using via middleware

You can protect your routes using middleware rules with a middleware `can` which is available out of the box:

```php
Route::match(['get', 'post'], 'post/view', 'PostController@view')->middleware('can:View post');
```

### Using via Blade directives

In `view` files it is convenient to use Blade directives:

```php
@authHasAny('Edit post', 'View post')
    // Showing info if a user can edit or view post 
@authEnd

@authHasAll('Edit post', 'View post')
    // Showing info if a user can edit and view post 
@authElse
    // Otherwise
@authEnd
```
For conditional permissions the following directives should be:

```php
@authCanAny(['Edit post', 'View post'], [1])
    // Showing info if a user can edit or view post with ID=1 
@authEnd

@authCanAll(['Edit post', 'View post'], [1])
    // Showing info if a user can edit and view post with ID=1
@authElse
    // Otherwise
@authEnd
```
More about conditional access in chapter [Advanced usage](#advanced-usage-of-permissions)

### Artisan commands

The current package provides a full list of necessary console commands to work with RBAC: 

```php
php artisan centeron:auth-item-create // Create a new auth item (role or description)
php artisan centeron:auth-items-remove // Remove auth items (roles and permissions)
php artisan centeron:auth-item-inherit // Add child items to chosen auth item
php artisan centeron:auth-item-disinherit // Remove childs from an auth item
php artisan centeron:auth-items-attach // Attach auth items to the model
php artisan centeron:auth-items-detach // Deattach auth items from the model
```

## Advanced usage of permissions

An overwhelming majority of RBAC usage doesn't need any logic for their triggering. We mark areas by string identifiers
and then check whether the user has permision for that particular area. Sometimes this is not enough. What if a user only needs to
edit their own posts or posts which belong to certain categories? What is more categories could be added in process of time.
In such situations, recoding isn’t always necessary.

We should be able, firstly, to provide the triggering logic to permissions and secondly, to retain relevant data which might be
required for these rules. 

For this purpose `Centeron\Permissions\Models\AuthItem` has 2 properties (columns in a table):

* `rule` - stores the name of the class responsible for triggering logic
* `data` - stores data in a serialized form that may be required working with the current object.

`rule` is a class which implements `Centeron\Permissions\Contracts` and has only one method `handle`.
If this method returns `true` then permission/role `AuthItem` will enable, if `false`, the current permission will be ignored.

As examples this package provides 3 rules out of the box:

**Access for permission `authItem` is available only on weekdays:**

```php
namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

class OnWeekdDays implements Rule
{
    public function handle($authItem, $model, $params = []): bool
    {
        return date('N') <= 5;
    }
}
```
**User only has access to own objects that he has created:**

```php
namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

class OnWeekdDays implements Rule
{
    public function handle($authItem, $model, $params = []): bool
    {
       $entityId = $params[0] ?? null;
       return $model->id === $entityId;
    }
}
```
In this case we have to provide information about the object (ID) as a parameter.

**User has access only to certain categories that have been listed in a database:**

```php
namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

class OnWeekdDays implements Rule
{
    public function handle($authItem, $model, $params = []): bool
    {
        $myCategories = unserialize($authItem['data']);
        return array_intersect($params, $myCategories) ? true : false;
    }
}
```
In this case we have to provide information about categories as parameters.
Also, information about categories with conditional access must be saved in the `data` field of the table.

Apparently each of users can be associated with a certain list of categories. In such situations you shoudn't create
new `AuthItem` with specific `data` and the same `rule` for each user. Rather, you can save data in a foreign table
and provide this data to the class as parameteres along with other information.

We perform admission checks on conditional permissions not via methods `hasAnyAuthItems` and `hasAllAuthItems`, but
with `canAnyAuthItems` and `canAnyAuthItems`, which require only 2 parameters. e first parameter is role/permission or 
an array of roles/permissions, and the second is an array of variables:

```php
$user->canAnyAuthItems(['View post', 'Edit post'], [1]); // true, possible edit or view a post with ID = 1
$user->hasAllAuthItems(['View post', 'Edit post'], [1]); // true, possible edit and view a post with ID = 1
```
The Blade directives work according these rules. The same is true of `authorize` method and `Gate` methods.

### Cache

By default the cache is disabled. In spite of a relatively large number of database request (from 3 to 5, depending of situation)
access checking performs quickly due to the simplicity of these requests to the database(select from the indexed columns of tables). 
However, if you want to get rid of it completely you can enable the cache. Cache gets and saves data of each tables.

Any changing of tables by the provided RBAC methods will reset the cache. You may also reset the cache manually:

```php
php artisan cache:forget centeron.permissions
```
Enable/disable cache, set the cache lifetime, its identifier you can by editing `config/permissions.php`.

Beware! In cases where the application has hundreds or thousands of roles and permisssions and their relations to users, the amount of used memory sometimes increases enormously
because the information from all tablesis cached. But processing time stays about the same.
Working with a disabled cache doesn't require a big amount of memory even with thousands of records in database tables because the requests are only 
linked with relevant data necessary for the current checking

### Database structure

To implement all work, Laravel RBAC needs only 3 database tables:

* `auth_items` - roles and permissions
* `auth_item_childs` - inheritance of roles and permissions
* `auth_assignments` - assignments roles/permissions and models

Table names can be changed in `config/permissions.php` before running the migration.

![Структура таблиц](https://github.com/centeron/laravel-roles-permissions/blob/master/docs/db.png)
