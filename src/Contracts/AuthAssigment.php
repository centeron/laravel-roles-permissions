<?php

namespace Centeron\Permissions\Contracts;

/**
 * Interface AuthAssigment
 * Attach auth items (roles and permissions) to models
 *
 * @package Centeron\Permissions\Contracts
 */
interface AuthAssigment
{
    /**
     * Leave only new items id (drop existing)
     *
     * @param array $itemIds
     * @param $model
     * @param $modelId
     * @return array
     */
    public static function leaveOnlyNewItemIds(array $itemIds, $model, $modelId): array;

    /**
     * Remove assigments
     *
     * @param array $itemIds
     * @param $model
     * @param $modelId
     * @return mixed
     */
    public static function remove(array $itemIds, $model, $modelId);

    /**
     * Add assigments
     *
     * @param array $itemIds
     * @param $model
     * @param $modelId
     * @return mixed
     */
    public static function add(array $itemIds, $model, $modelId);
}
