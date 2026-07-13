<?php

namespace Modules\Chain\Console\Commands;

use App\Console\Commands\BaseCommand;

abstract class ChainCommand extends BaseCommand
{
    protected $signature = 'chain:run';
}
