<?php

namespace Centeron\Permissions\Rules;

use Centeron\Permissions\Contracts\Rule;

/**
 * Class OnlyWeekdDays
 * @package Centeron\Permissions\Rules
 */
class OnWeekdDays implements Rule
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
        return date('N') <= 5;
    }
}