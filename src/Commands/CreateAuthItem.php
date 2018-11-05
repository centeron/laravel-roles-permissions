<?php

namespace Centeron\Permissions\Commands;

use Centeron\Permissions\Models\AuthItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Class CreateAuthItem
 * @package Centeron\Permissions\Commands
 */
class CreateAuthItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'centeron:auth-item-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new auth item (role or description)';

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
        $nameRules = ['name' => 'required|unique:'.config('permissions.table_names.auth_items').',name|regex:/^[a-zA-Z0-9\.\-]+$/i|max:255'];
        $name = $this->ask('Enter a name of auth item...');
        $validator = Validator::make(['name' => $name], $nameRules);
        while ($validator->fails()) {
            $this->error($validator->getMessageBag()->first());
            $name = $this->ask('Enter the name again...');
            $validator = Validator::make(['name' => $name], $nameRules);
        }

        $type = $this->choice('Enter a type of auth item... (1 - permission, 2 - role)', ['1' => AuthItem::TYPE_PERMISSION, '2' => AuthItem::TYPE_ROLE], AuthItem::TYPE_PERMISSION);
        $rule = $this->ask('Enter a rule classname...');

        if (AuthItem::create(['name' => $name, 'type' => $type, 'rule' => $rule])) {
            $this->info("New ".(($type === '1') ? "permission" : "role") . " '$name' added.");
        } else {
            $this->error("Error of auth item adding.");
        }
    }
}
