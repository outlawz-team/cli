<?php

namespace App\Commands\Traits;

use Illuminate\Support\Facades\Storage;

trait Stub
{
    public function stub($file, $replacements, $destination)
    {
        $stub = Storage::disk('internal')->get('stubs/' . $file . '.stub');

        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{" . $key . "}}", $value, $stub);
        }

        file_put_contents($destination, $stub);
    }
}