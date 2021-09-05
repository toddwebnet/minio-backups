<?php

namespace App\Console\Commands;

use App\Services\S3StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunJobs extends Command
{
    protected $signature = "jobs:run";

    public function handle()
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $job) {
            $job->options = json_decode($job->options, true);
            $this->processJob($job);
        }
    }

    private function getJobs()
    {
        $sql = "select * from jobs";
        return DB::select($sql);
    }

    private function getJobHistory($job, $path)
    {
        $sql = "select distinct source from uploads where path=? and job_id=?";

        $list = [];
        foreach (DB::select($sql, [$path, $job->id]) as $row){
            $list[] = $row->source;
        }

        return $list;
    }

    private function processJob($job)
    {
        $s3 = $s3 = new S3StorageService();
        $s3->setBucket($job->bucket);
        $dir = $job->path;
        $overwriteAlways = $job->options['overwrite_always'] ?? [];
        $ignore = $job->options['ignore'] ?? [];
        $pathPrefix = $job->options['pathPrefix'] ?? '';
        $includeDateStamp = $job->options['includeDateStamp'] ?? false;
        $preventDuplicates = $job->options['preventDuplicates'] ?? true;

        $path = $pathPrefix;
        if ($includeDateStamp) {
            $path .= '/' . date("Y/m/d", time());
        }

        $jobHistory = ($preventDuplicates) ? $this->getJobHistory($job, $path) : [];

        foreach (scandir($dir) as $file) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($fullPath)) {
                if ($this->allowedToWrite($file, $jobHistory, $preventDuplicates, $overwriteAlways, $ignore)) {
                    $this->line('Putting: ' . $file);
                    $s3->putObject($path . '/' . $file, fopen($fullPath, 'r'));
                    $this->addToHistory($preventDuplicates, $job, $path, $file);
                }
            }
        }
    }

    private function addToHistory($preventDuplicates, $job, $path, $file)
    {
        $sql = "insert into uploads (job_id, bucket, path, source) values (?,?,?,?)";
        $params = [$job->id, $job->bucket, $path, $file];
        DB::insert($sql, $params);
    }

    private function allowedToWrite($file, $jobHistory, $preventDuplicates, $overwriteAlways, $ignore)
    {
        if (in_array($file, $ignore)) {
            return false;
        }
        if (in_array($file, $overwriteAlways)) {
            return true;
        }
        if ($preventDuplicates && in_array($file, $jobHistory)) {
            return false;
        }
        return true;
    }
}
