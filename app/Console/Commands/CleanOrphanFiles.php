<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanOrphanFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:clean-orphans';

    protected $description = 'Scan storage for files not linked in the database and delete them';

    public function handle()
    {
        $this->info('Starting orphan files cleanup...');

        $validFiles = [];

        // 1. Single Image columns
        $validFiles = array_merge($validFiles, \Illuminate\Support\Facades\DB::table('users')->whereNotNull('image')->pluck('image')->toArray());
        $validFiles = array_merge($validFiles, \Illuminate\Support\Facades\DB::table('organizations')->whereNotNull('image')->pluck('image')->toArray());
        $validFiles = array_merge($validFiles, \Illuminate\Support\Facades\DB::table('places')->whereNotNull('image')->pluck('image')->toArray());
        $validFiles = array_merge($validFiles, \Illuminate\Support\Facades\DB::table('categories')->whereNotNull('image')->pluck('image')->toArray());
        $validFiles = array_merge($validFiles, \Illuminate\Support\Facades\DB::table('messages')->whereNotNull('attachment')->pluck('attachment')->toArray());

        // 2. JSON Array columns
        $chatMessages = \Illuminate\Support\Facades\DB::table('chat_messages')->whereNotNull('attachments')->pluck('attachments');
        foreach ($chatMessages as $attachments) {
            $arr = json_decode($attachments, true);
            if (is_array($arr)) {
                $validFiles = array_merge($validFiles, $arr);
            }
        }

        $postsImages = \Illuminate\Support\Facades\DB::table('posts')->whereNotNull('images')->pluck('images');
        foreach ($postsImages as $images) {
            $arr = json_decode($images, true);
            if (is_array($arr)) {
                $validFiles = array_merge($validFiles, $arr);
            }
        }

        $postsFiles = \Illuminate\Support\Facades\DB::table('posts')->whereNotNull('files')->pluck('files');
        foreach ($postsFiles as $files) {
            $arr = json_decode($files, true);
            if (is_array($arr)) {
                $validFiles = array_merge($validFiles, $arr);
            }
        }

        // Clean up formatting (normalize slashes)
        $validFiles = array_map(function ($path) {
            return str_replace('\\', '/', $path);
        }, $validFiles);
        $validFiles = array_unique($validFiles);

        // 3. Scan the public disk
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $allDiskFiles = $disk->allFiles();

        $deletedCount = 0;
        foreach ($allDiskFiles as $file) {
            // Ignore hidden files or root files like .gitignore
            if (str_starts_with($file, '.') || $file === '.gitignore') {
                continue;
            }

            $normalizedFile = str_replace('\\', '/', $file);

            if (!in_array($normalizedFile, $validFiles)) {
                $disk->delete($file);
                $this->info("Deleted orphan file: {$file}");
                $deletedCount++;
            }
        }

        $this->info("Cleanup finished. Total orphan files deleted: {$deletedCount}");
    }
}
