<?php

namespace Centeron\Permissions\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Interface AuthItem
 *
 * @package Centeron\Permissions\Contracts
 */
interface AuthItem
{
    const TYPE_PERMISSION = 1;
    const TYPE_ROLE = 2;

    /**
     * Create new Role or Permission
     *
     * @param array $attributes
     * @return Model
     */
    public static function create(array $attributes = []);

    /**
     * Create new Role
     *
     * @param array $attributes
     * @return Model
     */
    public static function createRole(array $attributes = []);

    /**
     * Create mew Permission
     *
     * @param array $attributes
     * @return Model
     */
    public static function createPermission(array $attributes = []);

    /**
     * Get All childs
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChilds($additionItems = []): Collection;

    /**
     * Get All Roles
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChildRoles($additionItems = []): Collection;

    /**
     * Get all Permissions
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChildPermissions($additionItems = []): Collection;

    /**
     * Add childs to the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function addChilds(...$items);

    /**
     * Add parents to the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function addParents(...$items);

    /**
     * Remove childs from the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return int
     */
    public function removeChilds(...$items);

    /**
     * Remove parents from the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return int
     */
    public function removeParents(...$items);

    /**
     * Attach current auth item to models
     *
     * @param array ...$models
     * @return void
     */
    public function attach(...$models);

    /**
     * Check if the current item has any of given items as childs
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function hasAny(...$items): bool;

    /**
     * Check if the current item has all of given items as childs
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function hasAll(...$items): bool;

    /**
     * Get All Child Ids
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return array
     */
    public function getChildIds($additionItems = []): array;

    /**
     * Get direct auth items (roles and permissions) for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return Collection
     */
    public static function getDirectAuthItemsForModel($model, $modelId): Collection;

    /**
     * Get direct roles for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return mixed
     */
    public static function getDirectRolesForModel($model, $modelId): Collection;

    /**
     * Get direct permissions for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return mixed
     */
    public static function getDirectPermissionsForModel($model, $modelId): Collection;

    /**
     * Fetch items id from the given array of different elements
     *
     * @param array $items - item of array could be ingteger, string or object
     * @return array
     */
    public static function fetchId($items = []): array;

    /**
     * Find items with rules by ids
     *
     * @param array $ids - items ids
     * @return Collection
     */
    public static function findWithRulesByIds($ids = []): Collection;

    /**
     * Relation to the assignment table
     *
     * @return HasMany
     */
    public function assignments(): HasMany;

    /**
     * Get Base Auth
     *
     * @return HasOne
     */
    public function baseAuth(): HasOne;

}