<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;

/**
 * Class DisinheritAuthItem
 * @package Centeron\Permissions\Commands
 */
class DisinheritAuthItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-item-disinherit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove childs from an auth item';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /** @var AuthItem|false|null $authItem */
        $authItem = false;
        while (empty($authItem)) {
            if ($authItem === null) {
                $this->error('Auth item with a passed name not found.');
            }
            $nameOrId = $this->ask('Enter a name of the auth item from which you want to remove childs...');
            $authItem = AuthItem::where('name', $nameOrId) ->first();
        }

        $childNames = $this->ask('List child names separated by commas for removing...');
        $childNames = array_map('trim', explode(',', $childNames));

        if ($authItem->removeChilds(...$childNames)) {
            $this->info("Childs removed from the auth item.");
        } else {
            $this->error("Error of removing childs from the auth item.");
        }
    }
}
