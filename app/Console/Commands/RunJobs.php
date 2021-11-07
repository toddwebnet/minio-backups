<?php

namespace App\Console\Commands;

use App\Services\S3StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunJobs extends Command
{
    protected $signature = "jobs:run {group}";

    public function handle()
    {
        $group = $this->argument('group');
        $jobs = $this->getJobs($group);
        foreach ($jobs as $job) {
            $job->options = json_decode($job->options, true);
            $this->processJob($job);
        }
    }

    private function getJobs($group)
    {
        $sql = "select * from jobs where grouping = ?";
        return DB::select($sql, [$group]);
    }

    private function getJobHistory($job, $path)
    {
        $sql = "select distinct source from uploads where path=? and job_id=?";

        $list = [];
        foreach (DB::select($sql, [$path, $job->id]) as $row) {
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
        $includeWeekStamp = $job->options['includeWeekStamp'] ?? false;
        $preventDuplicates = $job->options['preventDuplicates'] ?? true;

        $path = $pathPrefix;
        if ($includeDateStamp) {
            $path .= '/' . date("Y/m/d", time());
        }
        if ($includeWeekStamp) {
            $days = [
                '0' => 'sun',
                '1' => 'mon',
                '2' => 'tue',
                '3' => 'wed',
                '4' => 'thu',
                '5' => 'fri',
                '6' => 'sat',
            ];
            $day = $days[date("w", time())];
            $path .= '/' . $day;
        }

        $jobHistory = ($preventDuplicates) ? $this->getJobHistory($job, $path) : [];

        foreach (scandir($dir) as $file) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($fullPath)) {
                if ($this->allowedToWrite($file, $jobHistory, $preventDuplicates, $overwriteAlways, $ignore)) {
                    $this->line('Putting: ' . $file);
                    try {
                        $putObject = $s3->putObject($path . '/' . $file, fopen($fullPath, 'r'));
                    } catch (\Exception $e) {
                        $putObject = false;
                    }
                    if ($putObject === false) {
                        print "\n\n CANNOT CONNECT TO S3/MINIO \n\n";
                        exit();
                    }
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
