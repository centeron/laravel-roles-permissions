<?php

namespace Centeron\Permissions\Tests;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class AuthItemTest
 * @package App\Centeron\tests
 */
class AuthItemTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $role = AuthItem::createRole(['name' => 'test_role_1']);
        $permission_1 = AuthItem::createPermission(['name' => 'test_permission_1']);
        $permission_1_1 = AuthItem::createPermission(['name' => 'test_permission_1_1']);
        $permission_1_2 = AuthItem::createPermission(['name' => 'test_permission_1_2']);
        $permission_1_3 = AuthItem::create(['name' => 'test_permission_1_3', 'type' => AuthItem::TYPE_PERMISSION]);
        AuthItem::createPermission(['name' => 'test_permission_2']);
        AuthItem::createPermission(['name' => 'test_permission_3']);

        $role->addChilds($permission_1, 'test_permission_2');
        $permission_1->addChilds($permission_1_1, $permission_1_2->id);
        $permission_1_3->addParents($permission_1);
    }

    public function tearDown()
    {
        AuthItem::whereIn('name', ['test_role_1', 'test_permission', 'test_permission_1', 'test_permission_1_1',
            'test_permission_1_2', 'test_permission_1_3', 'test_permission_2', 'test_permission_3'])->delete();
    }

    public function testHasAny()
    {
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        /** @var AuthItem $permission_2 */
        $permission_2 = AuthItem::where('name', 'test_permission_2')->first();

        $this->assertTrue($role->hasAny('test_permission_1_1'));
        $this->assertTrue($role->hasAny('test_permission_1', 'test_permission_3'));
        $this->assertFalse($role->hasAny('test_permission_3'));
        $this->assertTrue($role->hasAny($permission_2, 'test_permission_3'));
        $this->assertTrue($role->hasAny($permission_2->id, 'test_permission_3'));
    }

    public function testHasAll()
    {
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        /** @var AuthItem $permission_2 */
        $permission_2 = AuthItem::where('name', 'test_permission_2')->first();

        $this->assertTrue($role->hasAll('test_permission_1_1', 'test_permission_1_2'));
        $this->assertFalse($role->hasAll('test_permission_1', 'test_permission_3'));
        $this->assertTrue($role->hasAll('test_permission_1', $permission_2, 'test_permission_1_1'));
    }

    public function testGetChilds()
    {
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        /** @var Collection $childs */
        $childs = $role->getChilds()->pluck('name');

        $this->assertCount(5, $childs);
        $this->assertContains('test_permission_1_3', $childs);
    }

    public function testGetChildRoles()
    {
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        /** @var Collection $childs */
        $childs = $role->getChildRoles();

        $this->assertEmpty($childs);
    }

    public function testGetChildPermissions()
    {
        /** @var AuthItem $role */
        $permission_1 = AuthItem::where('name', 'test_permission_1')->first();
        /** @var Collection $childs */
        $childs = $permission_1->getChildPermissions();

        $this->assertCount(3, $childs);
    }

    public function testRemoveChilds()
    {
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        $role->removeChilds('test_permission_1');
        $childs = $role->getChilds();

        $this->assertCount(1, $childs);
    }

    public function testRemoveParents()
    {
        /** @var AuthItem $permission_1 */
        $permission_1 = AuthItem::where('name', 'test_permission_1')->first();
        $permission_1->removeParents('test_role_1');
        /** @var AuthItem $role */
        $role = AuthItem::where('name', 'test_role_1')->first();
        $childs = $role->getChilds();
        $this->assertCount(1, $childs);

    }
}
