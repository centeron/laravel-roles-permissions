<?php

namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

/**
 * Class IAmCreator
 * @package Centeron\Permissions\Rules
 */
class IAmCreator implements Rule
{
    /**
     * Handle logic with passed $params
     *
     * @param $authItem
     * @param $model
     * @param array $params
     * @return bool
     */
    public function handle($authItem, $model, $params = []): bool
    {
        $entityId = $params[0] ?? null;

        return $model->id === $entityId;
    }
}