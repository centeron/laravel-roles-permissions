<?php

namespace Centeron\Permissions\Models;

use Centeron\Permissions\CacheStorage;
use Centeron\Permissions\Exceptions\AuthItemNotFound;
use Centeron\Permissions\Exceptions\WrongAuthItem;
use Illuminate\Database\Eloquent\Model;
use Centeron\Permissions\Contracts\AuthItem as AuthItemContract;
use Centeron\Permissions\Exceptions\AuthItemAlreadyExist;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Centeron\Permissions\Contracts\AuthAssigment;

/**
 * Auth Item (Roles, Permissions)
 *
 * @property integer $id
 * @property string $name
 * @property integer $type
 * @property string rule
 * @property string $data
 * @property string $created_at
 * @property string $updated_at
 *
 * @package Centeron\Permissions
 */
class AuthItem extends Model implements AuthItemContract
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'rule', 'name', 'type', 'data'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = static::TYPE_PERMISSION;
        }
        parent::__construct($attributes);

        $this->setTable(config('permissions.table_names.auth_items'));
    }

    /**
     * Create new Role or Permission
     *
     * @param array $attributes
     * @return $this|Model
     */
    public static function create(array $attributes = [])
    {
        if (!isset($attributes['type'])) {
            $attributes['type'] = static::TYPE_PERMISSION;
        }
        if (static::where('name', $attributes['name'])->count()) {
            throw new AuthItemAlreadyExist("Auth item with a name `{$attributes['name']}` already exists.");
        }

        return static::query()->create($attributes);
    }

    /**
     * Create new Role
     *
     * @param array $attributes
     * @return AuthItem|Model
     */
    public static function createRole(array $attributes = [])
    {
        $attributes['type'] = static::TYPE_ROLE;

        return static::create($attributes);
    }

    /**
     * Create mew Permission
     *
     * @param array $attributes
     * @return AuthItem|Model
     */
    public static function createPermission(array $attributes = [])
    {
        $attributes['type'] = static::TYPE_PERMISSION;

        return static::create($attributes);
    }

    /**
     * Get direct childs
     *
     * @return BelongsToMany
     */
    public function directChilds(): BelongsToMany
    {
        return $this->belongsToMany(static::class, config('permissions.table_names.auth_item_childs'),'parent_id', 'child_id');
    }

    /**
     * Get direct parents
     *
     * @return BelongsToMany
     */
    public function directParents(): BelongsToMany
    {
        return $this->belongsToMany(static::class, config('permissions.table_names.auth_item_childs'), 'child_id', 'parent_id');
    }

    /**
     * Assignments
     *
     * @return HasMany
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(app(AuthAssigment::class));
    }

    /**
     * Get All childs
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChilds($additionItems = []): Collection
    {
        $childIds = $this->getChildIds($additionItems);

        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use($childIds) {
                return in_array($value->id, $childIds);
            })
            : static::whereIn('id', $childIds)
                ->get();
    }

    /**
     * Get All Roles
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChildRoles($additionItems = []): Collection
    {
        $childIds = $this->getChildIds($additionItems);

        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use($childIds) {
                return ($value->type === static::TYPE_ROLE && in_array($value->id, $childIds));
            })
            : static::whereIn('id', $childIds)
                ->where('type', static::TYPE_ROLE)
                ->get();
    }

    /**
     * Get all Permissions
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return Collection
     */
    public function getChildPermissions($additionItems = []): Collection
    {
        $childIds = $this->getChildIds($additionItems);

        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use($childIds) {
                return ($value->type === static::TYPE_PERMISSION && in_array($value->id, $childIds));
            })
            : static::whereIn('id', $childIds)
                ->where('type', static::TYPE_PERMISSION)
                ->get();
    }

    /**
     * Add childs to the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function addChilds(...$items)
    {
        $itemIds = static::fetchId($items);

        $existingItemIds = DB::table(config('permissions.table_names.auth_item_childs'))
            ->whereIn('child_id', $itemIds)
            ->where('parent_id', $this->id)
            ->pluck('child_id')
            ->all();

        $newItemIds = array_diff($itemIds, $existingItemIds);
        $newItems = [];
        foreach ($newItemIds as $newItemId) {
            $newItems[] = [
                'child_id' => $newItemId,
                'parent_id' => $this->id
            ];
        }

        $result = DB::table(config('permissions.table_names.auth_item_childs'))->insert($newItems);
        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }

    /**
     * Add parents to the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function addParents(...$items)
    {
        $itemIds = static::fetchId($items);

        $existingItemIds = DB::table(config('permissions.table_names.auth_item_childs'))
            ->where('child_id', $this->id)
            ->whereIn('parent_id', $itemIds)
            ->pluck('parent_id')
            ->all();

        $newItemIds = array_diff($itemIds, $existingItemIds);
        $newItems = [];
        foreach ($newItemIds as $newItemId) {
            $newItems[] = [
                'child_id' => $this->id,
                'parent_id' => $newItemId
            ];
        }

        $result = DB::table(config('permissions.table_names.auth_item_childs'))->insert($newItems);
        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }

    /**
     * Remove childs from the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return int
     */
    public function removeChilds(...$items)
    {
        $itemIds = static::fetchId($items);

        $result = DB::table(config('permissions.table_names.auth_item_childs'))
            ->where('parent_id', $this->id)
            ->whereIn('child_id', $itemIds)
            ->delete();

        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }

    /**
     * Remove parents from the current item
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return int
     */
    public function removeParents(...$items)
    {
        $itemIds = static::fetchId($items);

        $result = DB::table(config('permissions.table_names.auth_item_childs'))
            ->whereIn('parent_id', $itemIds)
            ->where('child_id', $this->id)
            ->delete();

        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }

    /**
     * Attach current auth item to models
     *
     * @param array ...$models
     * @return void
     */
    public function attach(...$models)
    {
        foreach ($models as $model) {
            $className = get_class($model);
            $AuthAssigment = app(AuthAssigment::class);
            $newItem = $AuthAssigment::leaveOnlyNewItemIds([$this->id], $className, $model->id);
            if ($newItem) {
                $AuthAssigment::add([$this->id], $className, $model->id);
            }
        }
    }

    /**
     * Check if the current item has any of given items as childs
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function hasAny(...$items): bool
    {
        $itemIds = static::fetchId($items);
        $ownItemIds = $this->getChildIds();

        return array_intersect($itemIds, $ownItemIds) ? true : false;
    }

    /**
     * Check if the current item has all of given items as childs
     *
     * @param array ...$items - item of array could be integer, string or object
     * @return bool
     */
    public function hasAll(...$items): bool
    {
        $itemIds = static::fetchId($items);
        $ownItemIds = $this->getChildIds();

        return array_diff($itemIds, $ownItemIds) ? false : true;
    }

    /**
     * Get All Child Ids
     *
     * @param array $additionItems - addtitional items for which you need to find child ids
     * @return array
     */
    public function getChildIds($additionItems = []): array
    {
        $relations =  config('permissions.cache.enable')
            ? app(CacheStorage::class)->getInheritances()->toArray()
            : DB::table(config('permissions.table_names.auth_item_childs'))->get();

        $parents = [];
        foreach ($relations as $relation) {
            $parents[$relation->parent_id][] = $relation->child_id;
        }

        $childs = $branches = [];
        $additionItems[] = $this->id;
        foreach (array_unique($additionItems) as $id) {
            $branches[] = [$id];
            $this->getChildsRecursive($id, $parents, $childs, $branches);
        }

        return array_unique($childs);
    }

    /**
     * Fetch items id from the given array of different elements
     *
     * @param array $items - item of array could be integer, string or object
     * @return array
     * @throws AuthItemNotFound
     */
    public static function fetchId($items = []): array
    {
        $newItemIds = [];
        $newItemNames = [];

        foreach (array_unique($items) as $item) {
            if ($item instanceof AuthItem) {
                if (!is_int($item->id)) {
                    throw new WrongAuthItem("Auth item Id has to be an integer type. ". gettype($item->id) ." given.");
                }
                $newItemIds[] = $item->id;
            } elseif (is_int($item)) {
                $newItemIds[] = $item;
            } elseif (is_string($item)) {
                $newItemNames[] = $item;
            }
        }
        if($newItemNames) {
            $newItemIdsFromName = config('permissions.cache.enable')
                ? app(CacheStorage::class)->getAuthItems()->filter(function ($value) use($newItemNames) {
                    return in_array($value->name, $newItemNames);
                })->pluck('id')->all()
                : static::whereIn('name', $newItemNames)->pluck('id')->all();

            if (count($newItemIdsFromName) !== count($newItemNames)) {
                throw new AuthItemNotFound('Some of items have not found by their names.');
            }
            $newItemIds = array_merge($newItemIds, $newItemIdsFromName);
        }

        return array_unique($newItemIds);
    }

    /**
     * Find items with rules by ids
     *
     * @param array $ids - items ids
     * @return Collection
     */
    public static function findWithRulesByIds($ids = []): Collection
    {
        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use($ids) {
                return ($value->rule && in_array($value->id, $ids));
            })
            : static::whereNotNull('rule')->whereIn('id', $ids)->get();
    }

    /**
     * Get direct auth items (roles and permissions) for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return mixed
     */
    public static function getDirectAuthItemsForModel($model, $modelId): Collection
    {
        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use ($model, $modelId) {
                if (!empty($value->assignments)) {
                    foreach ($value->assignments as $assignment) {
                        if ($assignment->model === $model && $assignment->model_id === $modelId)
                            return true;
                    }
                }
                return false;
            })
            : static::whereHas('assignments', function ($query) use($model, $modelId) {
                $query->where('model', $model)->where('model_id', $modelId);
            })->get();
    }

    /**
     * Get direct roles for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return mixed
     */
    public static function getDirectRolesForModel($model, $modelId): Collection
    {
        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use ($model, $modelId) {
                if ($value->type === static::TYPE_ROLE &&!empty($value->assignments)) {
                    foreach ($value->assignments as $assignment) {
                        if ($assignment->model === $model && $assignment->model_id === $modelId)
                            return true;
                    }
                }
                return false;
            })
            : static::whereHas('assignments', function ($query) use($model, $modelId) {
                $query->where('model', $model)->where('model_id', $modelId);
            })->where('type', static::TYPE_ROLE)->get();
    }

    /**
     * Get direct permissions for the model
     *
     * @param $model - class name of model
     * @param $modelId - model Id
     * @return mixed
     */
    public static function getDirectPermissionsForModel($model, $modelId): Collection
    {
        return config('permissions.cache.enable')
            ? app(CacheStorage::class)->getAuthItems()->filter(function($value) use ($model, $modelId) {
                if ($value->type === static::TYPE_PERMISSION &&!empty($value->assignments)) {
                    foreach ($value->assignments as $assignment) {
                        if ($assignment->model === $model && $assignment->model_id === $modelId)
                            return true;
                    }
                }
                return false;
            })
            : static::whereHas('assignments', function ($query) use($model, $modelId) {
                $query->where('model', $model)->where('model_id', $modelId);
            })->where('type', static::TYPE_PERMISSION)->get();
    }

    /**
     * Collect all children by recursion
     *
     * @param $id - current object
     * @param $childList - all relations
     * @param $childs - result
     * @param $branches - contains all possible inheritances. Used for recognizing infinity loops in inheritances
     */
    protected function getChildsRecursive($id, $childList, &$childs, &$branches)
    {
        $currentBranchIdx = count($branches) - 1;
        if (isset($childList[$id])) {
            foreach ($childList[$id] as $childId) {
                $childs[] = $childId;
                if (end($branches[$currentBranchIdx]) === $id) {
                    if(in_array($childId, $branches[$currentBranchIdx])) {
                        // defense from an infinity loop of auth items. This item exists in this branch already.
                        break;
                    }
                    $branches[$currentBranchIdx][] = $childId;
                } else {
                    $newBranch = $branches[$currentBranchIdx];
                    while (end($newBranch) !== $id) {
                        array_pop($newBranch);
                    }
                    $newBranch[] = $childId;
                    $branches[] = $newBranch;
                }
                $this->getChildsRecursive($childId, $childList, $childs, $branches);
            }
        }
    }
}