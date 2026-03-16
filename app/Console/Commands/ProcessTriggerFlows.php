<?php

namespace App\Console\Commands;

use App\Services\TriggerFlowProcessorService;
use Illuminate\Console\Command;

class ProcessTriggerFlows extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'triggers:process {--batch=10 : Number of tasks to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending trigger flow tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing trigger flow tasks...');

        $batchSize = (int) $this->option('batch');

        try {
            $processor = new TriggerFlowProcessorService();
            $result = $processor->processPendingTasks('command');

            $this->info("Processed: {$result['processed']} tasks");
            $this->info("Failed: {$result['failed']} tasks");
            $this->info("Execution time: " . round($result['executionTime'], 4) . " seconds");

            if (!empty($result['errors'])) {
                $this->warn("Errors:");
                foreach ($result['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            return $result['failed'] > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
