<?php

namespace App\Blocks;

class Modal
{
    public function render(array $attributes, string $content): string
    {
        return view('blocks.modal', [
            'blockContent' => $content,
            'buttonText' => $attributes['buttonText'] ?? null,
            'heading' => $attributes['heading'] ?? null,
        ]);
    }
}
