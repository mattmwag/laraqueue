<?php

namespace App\Jobs;

use App\Task;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class Task
 * @package App\Jobs
 */
class ProcessTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * @var Task
     */
    private $task;
    
    /**
     * Create a new job instance.
     *
     * @param string $command
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->task->processor_id = getmypid();
        $this->task->began_processing = Carbon::now();
        $this->task->save();
        
        sleep(rand(1, 30));
        
        $this->task->completed_processing = Carbon::now();
        $this->task->save();
    }
}
