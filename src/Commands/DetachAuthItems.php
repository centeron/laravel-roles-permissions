<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthAssigment;
use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;

/**
 * Class DetachAuthItems
 * @package Centeron\Permissions\Commands
 */
class DetachAuthItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-items-detach';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deattach auth items from the model';

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
        $model = $this->ask('Enter a class name of the model (For example: App\User) you want to detach...');
        $modelId = $this->ask('Enter id of the model...');
        $authItemNames = $this->ask('List auth item names separated by commas which you want to detach from the model...');
        $authItemNames = array_map('trim', explode(',', $authItemNames));
        $authItemIds = AuthItem::fetchId($authItemNames);

        if (AuthAssigment::remove($authItemIds, $model, $modelId)) {
            $this->info("Auth items detached from the model.");
        } else {
            $this->error("Error of detaching of auth items.");
        }
    }
}
