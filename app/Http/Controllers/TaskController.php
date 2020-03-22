<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTask;
use App\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'submitter_id' => 'required|integer',
           'command' => 'required',
        ]);
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }
        
        $task = $this->createTask($request->submitter_id, $request->command);
        ProcessTask::dispatch($task);
        
        return [
            'success' => true,
            'task_id' => $task->id,
        ];
    }
    
    /**
     * @param Request $request
     * @return array
     */
    public function addMany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'submitter_id' => 'required|integer',
            'command' => 'required',
            'count' => 'required|integer'
        ]);
    
        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
            ];
        }
        
        $count = $request->count;
        while ($count > 0) {
            $task = $this->createTask($request->submitter_id, $request->command);
            ProcessTask::dispatch($task);
            $count--;
        }
    }
    
    /**
     * @return array
     */
    public function getPriority()
    {
        $task = Task::whereNull('processor_id')->first();
        if (!$task instanceof Task) {
            return [
                'success' => true,
                'message' => 'Task queue is empty',
            ];
        }
        return [
            'success' => true,
            'message' => 'Found next task id to be processed',
            'task_id' => $task->id,
        ];
    }
    
    /**
     * @param int $id
     * @return array
     */
    public function getStatus(int $id): array
    {
        $task = Task::find($id);
        
        if (!$task instanceof Task) {
            return [
                'success' => false,
                'message' => 'Task not found',
            ];
        }
        
        return [
            'success' => true,
            'message' => $this->calculateStatus($task),
        ];
    }
    
    /**
     * @param int $minutes
     * @return array
     */
    public function getAverageProcessingTime($minutes = 60)
    {
        // This somewhat whimsical caching mechanism stores a request for the length of minutes requested.
        // So if users are interested in 60 minute processing time time windows, this will cache for 60m.
        // A 2 minute window request will cache for 2 minutes. And so on.
        
        if (Cache::has("avgProcT{$minutes}")) {
            return Cache::pull("avgProcT{$minutes}");
        }
        
        return Cache::remember("avgProcT{$minutes}", $minutes * 60, function () use ($minutes) {
    
            $timeNow = Carbon::now();
            $timeSince = $timeNow->subMinutes($minutes);
    
            $tasks = Task::where('completed_processing', '<', $timeSince)
                ->get();
    
            $time = $tasks->map(function ($item) {
                return Carbon::parse($item->completed_processing)->diffInSeconds(
                    Carbon::parse($item->began_processing)
                );
            })->avg();
            
            return [
                'success'                            => true,
                'request_time'                       => $timeNow,
                'start_of_time_window'               => $timeSince,
                'average_processing_time_in_seconds' => $time,
            ];
        });
    }
    
    /**
     * @param int $submitterId
     * @param string $command
     * @return Task
     */
    private function createTask(int $submitterId, string $command): Task
    {
        $task = new Task();
        $task->submitter_id = $submitterId;
        $task->command = $command;
        $task->save();
        return $task;
    }
    
    /**
     * @param Task $task
     * @return string
     */
    private function calculateStatus(Task $task): string
    {
        if (!$task->processor_id) {
            return "Task is in queue";
        }
        if ($task->began_processing && !$task->completed_processing) {
            return "Task is processing. Began at {$task->began_processing}";
        }
        if ($task->completed_processing) {
            return "Task completed at {$task->completed_processing}";
        }
    }
}
