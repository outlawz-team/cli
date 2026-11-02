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

    /**
     * Insert a stub in a file, before the first anchor that is in it. Nothing changes when the file
     * already has the marker. Returns false when there is no anchor to insert it at.
     */
    public function insertStub($file, $destination, $marker, $anchors)
    {
        $contents = file_get_contents($destination);

        if (str_contains($contents, $marker)) {
            return true;
        }

        foreach ($anchors as $anchor) {
            $position = strpos($contents, $anchor);
            if ($position !== false) {
                $stub = Storage::disk('internal')->get('stubs/' . $file . '.stub');
                file_put_contents($destination, substr($contents, 0, $position) . $stub . substr($contents, $position));

                return true;
            }
        }

        return false;
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