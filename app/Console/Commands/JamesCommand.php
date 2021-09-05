<?php

namespace App\Console\Commands;

use App\Services\S3StorageService;
use Illuminate\Console\Command;

class JamesCommand extends Command
{
    protected $signature = 'james {bucket} {target_path} {file_local_path} ';

    public function handle()
    {
        dd($this->argument('s3_path'), $this->argument('file_path'));
//        $s3 = new S3StorageService();
//        $s3->setBucket('testing');
//
//        $string = "This is my file";
//        $stream = fopen('data://text/plain;base64,' . base64_encode($string), 'r');
//        $r = $s3->putObject('/word/up/dawg', $stream);
//        dd($r);
    }
}
