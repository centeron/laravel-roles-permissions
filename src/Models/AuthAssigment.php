<?php

namespace Centeron\Permissions\Models;

use Centeron\Permissions\CacheStorage;
use Illuminate\Database\Eloquent\Model;
use Centeron\Permissions\Contracts\AuthAssigment as AuthAssigmentContract;
use Illuminate\Database\Query\Expression;

/**
 * Class AuthAssigment
 *
 * @property integer $auth_item_id
 * @property string $model
 * @property integer $model_id
 * @property string $created_at
 * @property string $updated_at
 *
 * @package Centeron\Permissions
 */
class AuthAssigment extends Model implements AuthAssigmentContract
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'auth_item_id', 'model', 'model_id'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('permissions.table_names.auth_assignments'));
    }

    /**
     * Leave only new items id (drop existing)
     *
     * @param array $itemIds - items id
     * @param $model - class name
     * @param $modelId - class id
     * @return array
     */
    public static function leaveOnlyNewItemIds (array $itemIds, $model, $modelId): array
    {
        $existingItemIds = static::whereIn('auth_item_id', $itemIds)
            ->where('model', $model)
            ->where('model_id', $modelId)
            ->pluck('auth_item_id')
            ->all();

        return $newItemsId = array_diff($itemIds, $existingItemIds);
    }

    /**
     * Add assigments
     *
     * @param array $itemIds
     * @param $model
     * @param $modelId
     * @return mixed
     */
    public static function add(array $itemIds, $model, $modelId)
    {
        $newItems =[];
        $now = new Expression('now()');

        foreach ($itemIds as $itemId) {
            $newItems[] = [
                'auth_item_id' => $itemId,
                'model' => $model,
                'model_id' => $modelId,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        $result = static::insert($newItems);

        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }

    /**
     * Remove assigments
     *
     * @param array $itemIds
     * @param $model
     * @param $modelId
     * @return mixed
     */
    public static function remove(array $itemIds, $model, $modelId)
    {
        $result = static::whereIn('auth_item_id', $itemIds)
            ->where('model', $model)
            ->where('model_id', $modelId)
            ->delete();

        if ($result && config('permissions.cache.enable')) {
            app(CacheStorage::class)->forget();
        }

        return $result;
    }
}