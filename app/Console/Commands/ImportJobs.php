<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ImportJobs extends Command
{
    protected $signature = "jobs:import {devProd}";

    public function handle()
    {
        $devProd = $this->argument('devProd');
        $jobs = [
            'dev' => [
                [
                    'name' => 'test',
                    'path' => '/home/jtodd/Downloads',
                    'bucket' => 'testing',
                    'options' => [
                        'overwrite_always' => [
                            'file1.txt',
                        ],
                        'ignore' => [],
                        'includeDateStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'downloads'
                    ],
                ]
            ],
            'win' =>[
                [
                    'name' => 'test',
                    'path' => 'C:\inetpub\feInc\uploadnew\temp',
                    'bucket' => 'uploads',
                    'options' => [
                        'overwrite_always' => [
                            'import.csv',
                        ],
                        'ignore' => [],
                        'includeDateStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'single'
                    ],
                ],
                [
                    'name' => 'test',
                    'path' => 'C:\inetpub\feInc\uploadfeimulti\temp',
                    'bucket' => 'uploads',
                    'options' => [
                        'overwrite_always' => [
                            'import.csv',
                        ],
                        'ignore' => [],
                        'includeDateStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'multi'
                    ],
                ]
            ]
        ];
        if (isset($jobs[$devProd])) {
            $this->procImport($jobs[$devProd]);
        } else {
            $this->warn('No Jobs to process');
        }
    }

    private function procImport($jobs)
    {
        $dbPath = env('DB_DATABASE');
        if(file_exists($dbPath))
        unlink($dbPath);
        touch($dbPath);

        Artisan::call("migrate:refresh");
        foreach ($jobs as $job) {
            $sql = "insert into  jobs (name, path, bucket, options) values (?,?,?,?)";
           $params = [
               $job['name'],
               $job['path'],
               $job['bucket'],
               json_encode($job['options']),
           ];
           DB::insert($sql, $params);
        }
        dump(DB::select("select * from jobs"));
    }
}
