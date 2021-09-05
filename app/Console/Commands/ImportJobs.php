<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ImportJobs extends Command
{
    protected $signature = "jobs:import";

    public function handle()
    {
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
            'win-uploads' => [
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
            ],
            'win-sql' => [
                [
                    'name' => 'sql',
                    'path' => 'C:\sqlbackups',
                    'bucket' => 'uploads',
                    'options' => [
                        'overwrite_always' => [
                            'import.csv',
                        ],
                        'ignore' => [],
                        'includeDateStamp' => true,
                        'preventDuplicates' => false,
                        'pathPrefix' => 'single'
                    ],
                ],

            ]
        ];

        //

        $this->procImport($jobs);
    }

    private function procImport($jobs)
    {
        $dbPath = env('DB_DATABASE');
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
        touch($dbPath);

        Artisan::call("migrate:refresh");
        foreach($jobs as $group=>$collection)
        foreach ($collection as $job) {
            $sql = "insert into  jobs (grouping, name, path, bucket, options) values (?,?,?,?,?)";
            $params = [
                $group,
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
