<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;

/**
 * Class InheritAuthItem
 * @package Centeron\Permissions\Commands
 */
class InheritAuthItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-item-inherit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add child items to chosen auth item';

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
                $this->error('Auth item with passed not found.');
            }
            $nameOrId = $this->ask('Enter a name of the auth item to which you want to add childs...');
            $authItem = AuthItem::where('name', $nameOrId) ->first();
        }

        $childNames = $this->ask('List child names separated by commas...');
        $childNames = array_map('trim', explode(',', $childNames));

        if ($authItem->addChilds(...$childNames)) {
            $this->info("Childs added to the auth item.");
        } else {
            $this->error("Error of adding childs to the auth item.");
        }
    }
}
