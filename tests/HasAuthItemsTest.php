<?php

namespace Centeron\Permissions\Tests;

use Centeron\Permissions\Models\AuthItem;
use Centeron\Permissions\Traits\HasAuthItems;
use Tests\TestCase;

/**
 * Class HasAuthItemsTest
 * @package Centeron\Permissions\Tests
 */
class HasAuthItemsTest extends TestCase
{
    /** @var  HasAuthItems */
    public $user;

    public function setUp()
    {
        parent::setUp();

        $role = AuthItem::createRole(['name' => 'test_role_1']);
        $permission_1 = AuthItem::createPermission(['name' => 'test_permission_1']);
        $permission_1_1 = AuthItem::createPermission(['name' => 'test_permission_1_1']);
        $permission_1_2 = AuthItem::createPermission(['name' => 'test_permission_1_2']);
        $permission_1_3 = AuthItem::create([
            'name' => 'test_permission_1_3',
            'type' => AuthItem::TYPE_PERMISSION,
            'rule' => 'Centeron\Permissions\Rules\OnlyCertainCategories',
            'data' => serialize(['first', 'second'])]);
        AuthItem::createPermission(['name' => 'test_permission_2']);
        AuthItem::createPermission(['name' => 'test_permission_3']);

        $role->addChilds($permission_1, 'test_permission_2');
        $permission_1->addChilds($permission_1_1, $permission_1_2->id);
        $permission_1_3->addParents($permission_1);

        /** @var HasAuthItems $user */
        $user = User::create([
            'name' => 'test',
            'email' => 'test@centeron.io',
            'password' => 'test',
        ]);
        $user->attachAuthItems('test_role_1');
        $this->user = $user;
    }

    public function tearDown()
    {
        AuthItem::whereIn('name', ['test_role_1', 'test_permission_1', 'test_permission_1_1',
            'test_permission_1_2', 'test_permission_1_3', 'test_permission_2', 'test_permission_3'])->delete();

        User::where('email', 'test@centeron.io')->delete();
    }

    public function testGetDirectAuthItems()
    {
        $directAuthItems = $this->user->getDirectAuthItems()->pluck('name');

        $this->assertCount(1, $directAuthItems);
        $this->assertContains('test_role_1', $directAuthItems);
    }

    public function testGetAuthItems()
    {
        $authItems = $this->user->getAuthItems()->pluck('name');

        $this->assertCount(6, $authItems);
        $this->assertContains('test_permission_1_2', $authItems);
    }

    public function testGetDirectRoles()
    {
        $directRoles = $this->user->getDirectRoles()->pluck('name');

        $this->assertCount(1, $directRoles);
        $this->assertContains('test_role_1', $directRoles);
    }

    public function testGetRoles()
    {
        $roles = $this->user->getRoles()->pluck('name');

        $this->assertCount(1, $roles);
        $this->assertContains('test_role_1', $roles);
    }

    public function testGetDirectPermissions()
    {
        $this->assertEmpty($this->user->getDirectPermissions());
    }

    public function testGetPermissions()
    {
        $permissions = $this->user->getPermissions()->pluck('name');

        $this->assertCount(5, $permissions);
        $this->assertNotContains('test_role_1', $permissions);
    }

    public function testAttachAuthItems()
    {
        $this->user->attachAuthItems('test_permission_3');

        $this->assertContains('test_permission_3', $this->user->getDirectPermissions()->pluck('name'));
    }

    public function testDetachAuthItems()
    {
        $this->user->detachAuthItems('test_role_1');

        $this->assertEmpty($this->user->getDirectRoles());
    }

    public function testHasAnyAuthItems()
    {
        $this->assertTrue($this->user->hasAnyAuthItems('test_permission_1'));
        $this->assertFalse($this->user->hasAnyAuthItems('test_permission_3'));
        $this->assertTrue($this->user->hasAnyAuthItems('test_permission_1', 'test_permission_3'));
    }

    public function testHasAllAuthItems()
    {
        $this->assertTrue($this->user->hasAllAuthItems('test_permission_1', 'test_permission_2', 'test_permission_1_3'));
        $this->assertFalse($this->user->hasAllAuthItems('test_permission_1', 'test_permission_3'));
    }

    public function testcanAnyAuthItems()
    {
        $this->assertFalse($this->user->canAnyAuthItems('test_permission_1_3'));
        $this->assertTrue($this->user->canAnyAuthItems('test_permission_1_3', ['first']));
        $this->assertFalse($this->user->canAnyAuthItems('test_permission_1_3', ['third']));
    }

    public function testcanAllAuthItems()
    {
        $this->assertTrue($this->user->canAllAuthItems(['test_permission_1_3', 'test_permission_1'], ['first']));
        $this->assertFalse($this->user->canAllAuthItems(['test_permission_1_3', 'test_permission_1']));
    }
}
