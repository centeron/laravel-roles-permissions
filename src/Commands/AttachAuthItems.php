<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthAssigment;
use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;

/**
 * Class AttachAuthItems
 * @package Centeron\Permissions\Commands
 */
class AttachAuthItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-items-attach';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attach the auth item to the model';

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
        $model = $this->ask('Enter a class name of a model (For example: App\User) you want to attach...');
        $modelId = $this->ask('Enter id of the model...');
        $authItemNames = $this->ask('List auth item names separated by commas which you want to attach to the model...');
        $authItemNames = array_map('trim', explode(',', $authItemNames));
        $authItemIds = AuthItem::fetchId($authItemNames);

        if (AuthAssigment::add($authItemIds, $model, $modelId)) {
            $this->info("Auth item attached to the model.");
        } else {
            $this->error("Error of attaching of auth items.");
        }
    }
}
