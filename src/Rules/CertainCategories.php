<?php

namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

/**
 * Class OnlyCertainCategories
 * @package Centeron\Permissions\Rules
 */
class CertainCategories implements Rule
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
        $myCategories = unserialize($authItem['data']);

        return array_intersect($params, $myCategories) ? true : false;
    }
}