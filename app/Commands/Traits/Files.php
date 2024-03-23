<?php

namespace App\Commands\Traits;

use Illuminate\Support\Facades\Storage;

trait Files
{
    public function stub($file, $replacements, $destination)
    {
        $stub = Storage::disk('internal')->get('stubs/' . $file . '.stub');

        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{" . $key . "}}", $value, $stub);
        }

        file_put_contents($destination, $stub);
    }

    public function deleteFile($destination)
    {
        if (file_exists($destination)) {
            unlink($destination);
        }
    }

    public function deleteDirectory($destination)
    {
        if (is_dir($destination)) {
            $files = glob($destination . '/*');
            foreach ($files as $file) {
                is_dir($file) ? $this->deleteDirectory($file) : unlink($file);
            }
            rmdir($destination);
        }
    }

    public function emptyDirectory($destination)
    {
        $files = glob($destination . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : unlink($file);
        }
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
    }
}