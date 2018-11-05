<?php

namespace Centeron\Permissions\Contracts;

/**
 * Additional logic for permorm auth items (roles, permissions)
 *
 * Interface Rule
 * @package Centeron\Permissions\Contracts
 */
interface Rule
{
    /**
     * Handle logic with passed $params
     *
     * @param $authItem
     * @param $model
     * @param array $params
     * @return bool
     */
    public function handle(AuthItem $authItem, $model, $params = []): bool;
}