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
                    'name' => 'dev',
                    'path' => '/home/jtodd/Downloads',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [
                            'file1.txt',
                        ],
                        'ignore' => [],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'downloads'
                    ],
                ]
            ],
            'win-uploads' => [
                [
                    'name' => 'uploads',
                    'path' => 'C:\inetpub\feInc\uploadnew\temp',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => ['import.csv'],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'single'
                    ],
                ],
                [
                    'name' => 'uploads',
                    'path' => 'C:\inetpub\feInc\uploadfeimulti\temp',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => ['import.csv'],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'multi'
                    ],
                ]
            ],
            'win-sql' => [
                [
                    'name' => 'sql',
                    'path' => 'C:\sqlbackups',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => true,
                        'preventDuplicates' => false,
                        'pathPrefix' => 'mssql'
                    ],
                ],

            ],
            'linux-sql' => [
                [
                    'name' => 'sql',
                    'path' => '/home/jtodd/backups/sql',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => true,
                        'preventDuplicates' => false,
                        'pathPrefix' => 'mysql'
                    ],
                ],
            ],
            'linux-apps' => [
                [
                    'name' => 'apps',
                    'path' => '/home/jtodd/backups/apps',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => true,
                        'preventDuplicates' => false,
                        'pathPrefix' => 'apps'
                    ],
                ],
            ],

            'salvador' => [
                [
                    'name' => 'backups',
                    'path' => '/home/jtodd/data/backups',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => 'single'
                    ],
                ],
                [
                    'name' => 'dockers',
                    'path' => '/home/jtodd/dockers',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => ''
                    ],
                ],
                [
                    'name' => 'projects',
                    'path' => '/home/jtodd/projects',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => true,
                        'pathPrefix' => ''
                    ],
                ],
                [
                    'name' => 'sql',
                    'path' => '/home/jtodd/backups',
                    'bucket' => 'fei-backups',
                    'options' => [
                        'overwrite_always' => [],
                        'ignore' => [],
                        'includeWeekStamp' => false,
                        'preventDuplicates' => false,
                        'pathPrefix' => ''
                    ],
                ],
            ]
        ];

        //

        $this->procImport($jobs);
    }

    private function procImport($jobs)
    {
        Artisan::call("migrate:fresh");
        foreach ($jobs as $group => $collection) {
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
        }
        dump(DB::select("select * from jobs"));
    }
}
