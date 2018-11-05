<?php

namespace Centeron\Permissions;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Support\Collection;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\DB;

/**
 * Auth data storage
 *
 * Class Storage
 * @package Centeron\Permissions
 */
class CacheStorage
{
    /** @var Repository */
    protected $cache;

    /**
     * CacheStorage constructor.
     * @param Repository $cache
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Load database data from the cache
     *
     * @return array
     */
    public function loadFromCache(): array
    {
        return $this->cache->remember(config('permissions.cache.key_name'), config('permissions.cache.cache_lifetime'), function () {
            $authItems = $this->getAllAuthItems();
            $inheritances = $this->getAllInheritances();

            return [$authItems, $inheritances];
        });
    }

    /**
     * Get all auth items (roles and permissions)
     * (using the cache if it has been enabled)
     *
     * @return Collection
     */
    public function getAuthItems() : Collection
    {
        return collect($this->loadFromCache()[0]);
    }

    /**
     * Get all inheritances
     * (using the cache if it has been enabled)
     *
     * @return Collection
     */
    public function getInheritances() : Collection
    {
        return collect($this->loadFromCache()[1]);
    }

    /**
     * Reset cache with all auth data
     */
    public function forget()
    {
        return $this->cache->forget(config('permissions.permissions.cache.key_name'));
    }

    /**
     * Get all auth items (roles and permissions)
     *
     * @return Collection
     */
    protected function getAllAuthItems(): Collection
    {
        return app(AuthItem::class)->with('assignments:auth_item_id,model,model_id')
            ->get(['id', 'name',  'type', 'rule', 'data']);
    }

    /**
     * Get all inheritances
     *
     * @return Collection
     */
    protected function getAllInheritances(): Collection
    {
        return DB::table(config('permissions.table_names.auth_item_childs'))->get();
    }
}