<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PintCommand extends Command
{
    protected $signature = 'pint';

    protected $description = 'Run Laravel Pint to fix code style issues';

    public function handle()
    {
        $process = new Process(['./vendor/bin/pint']);
        $process->setWorkingDirectory(base_path());

        try {
            $process->mustRun();
            $this->info('Pint finished successfully!');
        } catch (ProcessFailedException $exception) {
            $this->error('Pint failed to run.');
        }
    }
}
