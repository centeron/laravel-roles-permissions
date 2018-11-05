<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;

/**
 * Class RemoveAuthItems
 * @package Centeron\Permissions\Commands
 */
class RemoveAuthItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-items-remove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove auth items (roles and permissions)';

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
        $itemNames = $this->ask('List item names separated by commas which you want to delete...');
        $itemNames = array_map('trim', explode(',', $itemNames));

        if (AuthItem::whereIn('name', $itemNames)->delete()) {
            $this->info("Auth items deleted.");
        } else {
            $this->error("Error of deleting auth items.");
        }

    }
}
