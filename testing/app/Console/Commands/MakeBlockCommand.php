<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeBlockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:block {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new block';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');

        if (! $name) {
            $name = $this->ask('What is the name of the block?');
        }
        $this->info("Creating block {$name}...");

        $studlyName = str_replace(' ', '', ucwords(str_replace('-', ' ', $name)));
        $kebabName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-\0', $studlyName));

        $this->createBlockClass($studlyName, $kebabName);
        $this->createBlockView($kebabName);
        $this->createBlockJs($studlyName, $kebabName);
        $this->updateEditorJs($studlyName, $kebabName);
        $this->updateBlocksServiceProvider($studlyName, $kebabName);

        $this->info("Block {$name} created successfully.");

        return 0;
    }

    protected function createBlockClass($studlyName, $kebabName)
    {
        $path = app_path("Blocks/{$studlyName}.php");

        if (file_exists($path)) {
            $this->error("Block class already exists: {$path}");
            return;
        }

        $content = <<<'EOT'
<?php

namespace App\Blocks;

class {$studlyName}
{
    public function render(string $blockContent, array $block): string
    {
        return view('blocks.{$kebabName}', [
            'block' => $block,
            'blockContent' => $blockContent,
        ]);
    }
}

EOT;

        $content = str_replace(['{$studlyName}', '{$kebabName}'], [$studlyName, $kebabName], $content);

        file_put_contents($path, $content);
    }

    protected function createBlockView($kebabName)
    {
        $path = resource_path("views/blocks/{$kebabName}.blade.php");

        if (file_exists($path)) {
            $this->error("Block view already exists: {$path}");
            return;
        }

        $content = <<<'EOT'
<div>
    <!-- Block: {$kebabName} -->
</div>
EOT;

        $content = str_replace('{$kebabName}', $kebabName, $content);

        file_put_contents($path, $content);
    }

    protected function createBlockJs($studlyName, $kebabName)
    {
        $path = resource_path("js/editor/{$kebabName}.block.jsx");

        if (file_exists($path)) {
            $this->error("Block JS already exists: {$path}");
            return;
        }

        $content = <<<'EOT'
import { InnerBlocks, useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";

/* Block name */
export const name = `radicle/{$kebabName}`;

/* Block title */
export const title = __(`{$studlyName}`, `radicle`);

/* Block category */
export const category = `design`;

/* Block attributes */
export const attributes = {};

/* Block edit */
export const edit = () => {
  const props = useBlockProps();

  return (
    <div {...props}>
      <InnerBlocks />
    </div>
  );
};

/* Block save */
export const save = () => <InnerBlocks.Content />;

EOT;

        $content = str_replace(['{$studlyName}', '{$kebabName}'], [$studlyName, $kebabName], $content);

        file_put_contents($path, $content);
    }

    protected function updateEditorJs($studlyName, $kebabName)
    {
        $path = resource_path('js/editor.js');
        $content = file_get_contents($path);

        $camelName = lcfirst($studlyName);

        // Add import statement
        $import = "import * as {$camelName}Block from \"./editor/{$kebabName}.block\";";
        $content = preg_replace('/(import .* from ".*\n)/', "$1{$import}\n", $content, 1);

        // Add registerBlockType call
        $register = <<<'EOT'
  registerBlockType({$camelName}Block.name, {
    apiVersion: 3,
    title: {$camelName}Block.title,
    category: {$camelName}Block.category,
    attributes: {$camelName}Block.attributes,
    edit: {$camelName}Block.edit,
    save: {$camelName}Block.save,
  });
EOT;
        $register = str_replace('{$camelName}', $camelName, $register);
        $content = str_replace(
            "  /**\n   * Register blocks\n   */",
            "  /**\n   * Register blocks\n   */\n  {$register}",
            $content
        );

        file_put_contents($path, $content);
    }

    protected function updateBlocksServiceProvider($studlyName, $kebabName)
    {
        $path = app_path('Providers/BlocksServiceProvider.php');
        $content = file_get_contents($path);

        if ($content === false) {
            $this->error("Could not read BlocksServiceProvider.php");
            return;
        }

        // 1) Ensure use statement exists
        $useStatement = "use App\\Blocks\\{$studlyName};";
        if (strpos($content, $useStatement) === false) {
            // Insert after the last existing "use ..." or after namespace if none
            if (preg_match('/^(namespace\s+[^;]+;\s*(?:\Ruse\s+[^;]+;\s*)*)/m', $content, $m)) {
                $block = $m[1];
                $replacement = rtrim($block) . PHP_EOL . $useStatement . PHP_EOL;
                $content = preg_replace('/^(namespace\s+[^;]+;\s*(?:\Ruse\s+[^;]+;\s*)*)/m', $replacement, $content, 1);
            } else {
                // Fallback: insert after namespace line
                $content = preg_replace('/^(namespace\s+[^;]+;\s*)/m', '$0' . $useStatement . PHP_EOL, $content, 1);
            }
        }

        // 2) Prepare the filter call we want to add
        $filterCall = "        /**\n"
            . "         * Render `radicle/{$kebabName}` block with Blade template\n"
            . "         */\n"
            . "        add_filter('render_block_radicle/{$kebabName}', [new {$studlyName}(), 'render'], 10, 2);";

        // Skip if it's already present
        if (strpos($content, $filterCall) === false) {
            // Insert inside boot() just before its closing brace
            // - Match the boot() method body lazily, then insert before the final }
            $pattern = '/(public\s+function\s+boot\s*\(\s*\)\s*\{\s*)(.*?)(\R\s*\})/s';
            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    '$1$2' . PHP_EOL . $filterCall . '$3',
                    $content,
                    1
                );
            } else {
                $this->error("Could not find boot() method in BlocksServiceProvider.php");
                return;
            }
        }

        if (file_put_contents($path, $content) === false) {
            $this->error("Failed to write BlocksServiceProvider.php");
            return;
        }

        $this->info("BlocksServiceProvider updated.");
    }
}
