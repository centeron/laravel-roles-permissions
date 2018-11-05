<?php

namespace Centeron\Permissions\Contracts;

use Illuminate\Support\Collection;

/**
 * Interface HasAuthItems is just for method`s descriptions
 * of models which could assigned at roles and permissions
 * by trait usage
 *
 * @package Centeron\Permissions\Contracts
 */
interface HasAuthItems
{
    /**
     * Get auth items (roles and permissions)
     * which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectAuthItems(): Collection;

    /**
     * Get all auth items (roles and permissions)
     * which belong to the current model
     *
     * @return Collection
     */
    public function getAuthItems(): Collection;

    /**
     * Get roles which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectRoles(): Collection;

    /**
     * Get all roles which belong to the current model
     *
     * @return Collection
     */
    public function getRoles(): Collection;

    /**
     * Get permissions which belong to the current model directly
     *
     * @return Collection
     */
    public function getDirectPermissions(): Collection;

    /**
     * Get all permissions which belong to the current model
     *
     * @return Collection
     */
    public function getPermissions(): Collection;

    /**
     * Assign auth items to the current model
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return mixed
     */
    public function attachAuthItems(...$items);

    /**
     * Remove auth items from the current model
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return mixed
     */
    public function detachAuthItems(...$items);

    /**
     * Check if the current model has any of given items
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return bool
     */
    public function hasAnyAuthItems(...$items): bool;

    /**
     * Check if the current model has all of given items
     *
     * @param array ...$items - items of array could be ingeger, string or object type
     * @return bool
     */
    public function hasAllAuthItems(...$items): bool;

    /**
     * Check if the current model can perform actions according any of these items
     *
     * @param array|integer|string|AuthItem $items - items of array could be ingeger, string or object type
     * @param array $params - additional parameters
     * @return bool
     */
    public function canAnyAuthItems($items, $params) : bool;

    /**
     * Check if the current model can perform actions according all these items
     *
     * @param array|integer|string|AuthItem $items - items of array could be ingeger, string or object type
     * @param array $params - additional parameters
     * @return bool
     */
    public function canAllAuthItems($items, $params) : bool;

}