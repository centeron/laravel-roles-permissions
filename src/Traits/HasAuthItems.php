<?php

namespace Centeron\Permissions\Traits;

use Centeron\Permissions\CacheStorage;
use Centeron\Permissions\Contracts\AuthAssigment;
use Centeron\Permissions\Contracts\AuthItem;
use Centeron\Permissions\Contracts\Rule;
use Centeron\Permissions\Exceptions\RuleNotFound;
use Illuminate\Support\Collection;

/**
 * Trait HasAuthItems used as a manager of
 * roles and permissions for the current model
 *
 * @package Centeron\Permissions
 */
trait HasAuthItems
{
    /**
     * Get auth items (roles and permissions)
     * which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectAuthItems(): Collection
    {
        return app(AuthItem::class)::getDirectAuthItemsForModel(self::class, $this->id);
    }

    /**
     * Get all auth items (roles and permissions)
     * which belong to the current model
     *
     * @return Collection
     */
    public function getAuthItems(): Collection
    {
        $directAuthItems = $this->getDirectAuthItems();
        $authItems = clone $directAuthItems;
        /** @var AuthItem $firstDirectAuthItem */
        $firstDirectAuthItem = $directAuthItems->shift();

        if ($firstDirectAuthItem) {
            $otherDirectAuthItemIds = $directAuthItems->pluck('id')->all();
            // Pass auth items as additional param in the first item instead of loop usage with concatenation
            $allChilds = $firstDirectAuthItem->getChilds($otherDirectAuthItemIds);
            $authItems = $authItems->merge($allChilds);
        }

         return $authItems;
    }

    /**
     * Get roles which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectRoles(): Collection
    {
        return app(AuthItem::class)::getDirectRolesForModel(self::class, $this->id);
    }

    /**
     * Get all roles which belong to the current model
     *
     * @return Collection
     */
    public function getRoles(): Collection
    {
        $roles = $this->getDirectRoles();
        $directAuthItems = $this->getDirectAuthItems();
        /** @var AuthItem $firstDirectAuthItem */
        $firstDirectAuthItem = $directAuthItems->shift();

        if ($firstDirectAuthItem) {
            $otherDirectAuthItemIds = $directAuthItems->pluck('id')->all();
            // Pass auth items as additional param in the first item instead of loop usage with concatenation
            $allChildRoles = $firstDirectAuthItem->getChildRoles($otherDirectAuthItemIds);
            $roles = $roles->merge($allChildRoles);
        }

        return $roles;
    }

    /**
     * Get permissions which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectPermissions(): Collection
    {
        return app(AuthItem::class)::getDirectPermissionsForModel(self::class, $this->id);
    }

    /**
     * Get all permissions which belong to the current model
     *
     * @return Collection
     */
    public function getPermissions(): Collection
    {
        $permissions = $this->getDirectPermissions();
        $directAuthItems = $this->getDirectAuthItems();
        /** @var AuthItem $firstDirectAuthItem */
        $firstDirectAuthItem = $directAuthItems->shift();

        if ($firstDirectAuthItem) {
            $otherDirectAuthItemIds = $directAuthItems->pluck('id')->all();
            // Pass auth items as additional param in the first item instead of loop usage with concatenation
            $allChildPermissions = $firstDirectAuthItem->getChildPermissions($otherDirectAuthItemIds);
            $permissions = $permissions->merge($allChildPermissions);
        }

        return $permissions;
    }

    /**
     * Assign auth items to the current model
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return mixed
     */
    public function attachAuthItems(...$items)
    {
        $itemIds = app(AuthItem::class)::fetchId($items);
        $assigment = app(AuthAssigment::class);
        $newItemIds = $assigment::leaveOnlyNewItemIds($itemIds, self::class, $this->id);

        return $assigment::add($newItemIds, self::class, $this->id);
    }

    /**
     * Remove auth items from the current model
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return mixed
     */
    public function detachAuthItems(...$items)
    {
        $deleteItemIds = app(AuthItem::class)::fetchId($items);

        return app(AuthAssigment::class)::remove($deleteItemIds, self::class, $this->id);
    }

    /**
     * Check if the current model has any of given items
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return bool
     */
    public function hasAnyAuthItems(...$items): bool
    {
       $givenItemIds = app(AuthItem::class)::fetchId($items);
       $authItemIds = $this->getAuthItems()->pluck('id')->all();

        return !empty(array_intersect($authItemIds, $givenItemIds)) ? true : false;
    }

    /**
     * Check if the current model has all of given items
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return bool
     */
    public function hasAllAuthItems(...$items): bool
    {
        $givenItemIds = app(AuthItem::class)::fetchId($items);
        $authItemIds = $this->getAuthItems()->pluck('id')->all();

        return array_diff($givenItemIds, $authItemIds) ? false : true;
    }

    /**
     * Check if the current model can perform actions according any of these items
     *
     * @param array|integer|string|AuthItem $items - items of array could be integer, string or object type
     * @param array $params - additional parameters
     * @return bool
     * @throws RuleNotFound
     */
    public function canAnyAuthItems($items, $params = []) : bool
    {
        $authItems = $this->canAuthItems($items, $params);

        return in_array(true, $authItems);
    }

    /**
     * Check if the current model can perform actions according all these items
     *
     * @param array|integer|string|AuthItem $items - items of array could be ingeger, string or object type
     * @param array $params - additional parameters
     * @return bool
     * @throws RuleNotFound
     */
    public function canAllAuthItems($items, $params = []) : bool
    {
        $givenItemIds = app(AuthItem::class)::fetchId($items);
        $authItemIds = $this->getAuthItems()->pluck('id')->all();

        return array_diff($givenItemIds, $authItemIds) ? false : true;
    }

    /**
     * Check if the current model can perform actions
     *
     * @param array|integer|string|AuthItem $items - items of array could be ingeger, string or object type
     * @param array $params - additional parameters
     * @return array
     * @throws RuleNotFound
     */
    public function canAuthItems($items, $params = []) : array
    {
        if (!is_array($items)) {
            $authItems[] = $items;
        } else {
            $authItems = $items;
        }
        $givenItemIds = app(AuthItem::class)::fetchId($authItems);
        $givenAuthItems = (config('permissions.cache.enable'))
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use ($givenItemIds) {
                return (in_array($value['id'], $givenItemIds) || in_array($value['base_auth_id'], $givenItemIds)); })
                ->toArray()
            : app(AuthItem::class)::whereIn('id', $givenItemIds)
                ->orWhereIn('base_auth_id', $givenItemIds)
                ->get()
                ->toArray();
        $authItemIds = $this->getAuthItems()->pluck('id')->all();

        $items = [];
        foreach ($givenAuthItems as $givenAuthItem) {
            if (!in_array($givenAuthItem['id'], $authItemIds)) {
                $items[$givenAuthItem['name']] = false;
            } else {
                if ($givenAuthItem['rule']) {
                    if (!class_exists($givenAuthItem['rule'])) {
                        throw new RuleNotFound("Rule class " .$givenAuthItem['rule'] . " not found.");
                    }
                    /** @var Rule $rule */
                    $rule = new $givenAuthItem['rule'];
                    if (!($rule instanceof Rule)) {
                        throw new RuleNotFound("Rule class " . $givenAuthItem['rule'] . " not found.");
                    }
                    $items[$givenAuthItem['name']] = $rule->handle($givenAuthItem, $this, $params);
                } else {
                    $items[$givenAuthItem['name']] = true;
                }
            }
        }

        return $items;
    }
}