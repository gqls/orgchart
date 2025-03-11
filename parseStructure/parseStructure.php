<?php

declare(strict_types=1);

namespace ParseStructure;

class ParseStructure
{
    public function __construct(private string $contents)
    {
    }

    /**
     * Parse the directory structure from a text representation
     *
     * @return array<string, array|null>
     *     keys are directory/file names
     *     values are either arrays for directories or null for files (not strings)
     */
    public function parseStructure(): array
    {
        $structure = [];
        $lines = explode("\n", trim($this->contents));
        $currentPath = [];

        foreach ($lines as $line) {
            // Skip empty lines
            if (empty(trim($line))) {
                continue;
            }

            // Calculate indentation based on the original line
            preg_match('/^[^\w\d\.]+/', $line, $matches);
            $indentMatch = $matches[0] ?? '';
            $indentLevel = strlen($indentMatch) > 0 ? intval(strlen($indentMatch) / 4) : 0;

            // Clean the line for content processing - remove tree characters and leading/trailing spaces
            $lineContent = preg_replace('/^[^\w\d\.]*(\S.*?)$/', '$1', $line);

            // Remove comments if any
            if (strpos($lineContent, '#') !== false) {
                $lineContent = trim(substr($lineContent, 0, strpos($lineContent, '#')));
            }

            // Skip if line is empty after removing comments
            if (empty($lineContent)) {
                continue;
            }

            // Adjust current path based on indentation
            while (count($currentPath) > $indentLevel) {
                array_pop($currentPath);
            }

            // Handle directory or file
            $name = rtrim($lineContent, '/');
            $isDirectory = str_ends_with($lineContent, '/');

            // Add to structure
            $this->addToStructure($structure, $currentPath, $name, $isDirectory);

            // If it's a directory, add to current path for next items
            if ($isDirectory) {
                $currentPath[] = $name;
            }
        }

        return $structure;
    }

    /**
     * Add an item to the structure at the specified path
     *
     * @param array<string, array|null> &$structure Reference to the structure being built
     * @param string[] $path Current directory path in the hierarchy
     * @param string $name Name of the item to add (file or directory)
     * @param bool $isDirectory Whether the item is a directory (true) or file (false)
     */
    private function addToStructure(array &$structure, array $path, string $name, bool $isDirectory): void
    {
        // Navigate to correct position in the structure
        $current = &$structure;
        foreach ($path as $dir) {
            if (!isset($current[$dir])) {
                $current[$dir] = [];
            }
            // Reassign $current to one level deeper
            $current = &$current[$dir];
        }

        // Add the item
        if ($isDirectory) {
            $current[$name] = [];
        } else {
            $current[$name] = null;
        }
    }

    /**
     * Create the directory structure on disk
     *
     * @param string $basePath Base path where to create the structure
     */
    public function createDirectoryStructure(string $basePath): void
    {
        $structure = $this->parseStructure();

        // Debug - print the structure
        echo "Parsed structure:\n";
        print_r($structure);

        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }
        echo "Directory structure created at: $basePath\n";

        $this->createStructure($basePath, $structure);
    }

    /**
     * Recursively create the directory structure
     *
     * @param string $currentPath Current path being processed
     * @param array<string, array|null> $items Items to create at this path
     */
    protected function createStructure(string $currentPath, array $items): void
    {
        foreach ($items as $name => $content) {
            $path = $currentPath . DIRECTORY_SEPARATOR . $name;

            if ($content === null) {
                // This is a file - no recursion needed
                if (!file_exists($path)) {
                    touch($path);
                    echo "Created file: $path\n";

                    // Add basic PHP namespace for .php files
                    if (str_ends_with($name, '.php')) {
                        $this->generatePhpFileWithNamespace($path, $currentPath);
                    }
                }
            } else {
                // Directory - create and then process its contents
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                    echo "Created directory: $path\n";
                }

                // Recurse to handle contents of this directory
                $this->createStructure($path, $content);
            }
        }
    }

    /**
     * Generate a PHP file with the appropriate namespace
     *
     * @param string $filePath Path to the PHP file
     * @param string $directoryPath Path to the directory containing the file
     */
    private function generatePhpFileWithNamespace(string $filePath, string $directoryPath): void
    {
        $projectRoot = dirname($directoryPath);
        $relativeDirPath = substr($directoryPath, strlen($projectRoot) + 1);
        $namespaceParts = explode(DIRECTORY_SEPARATOR, $relativeDirPath);

        // Filter out empty namespace parts
        $namespaceParts = array_filter($namespaceParts);

        // Change to uppercase first namespace format
        $namespaceParts = array_map(function ($part) {
            return ucfirst($part);
        }, $namespaceParts);

        $namespace = implode('\\', $namespaceParts);
        $fileContent = empty($namespace) ? "<?php\n" : "<?php\n\nnamespace $namespace;\n";
        file_put_contents($filePath, $fileContent);
    }
}

// Entry point
if (file_exists('./structure.txt')) {
    $contents = file_get_contents('./structure.txt');

    if ($contents !== false) {
        $structurer = new ParseStructure($contents);
        $dirs = scandir(__DIR__);

        if (!in_array('output', $dirs)) {
            mkdir(__DIR__.'/output');
        }

        $structurer->createDirectoryStructure(__DIR__.'/output');
    } else {
        echo "Failed to read structure.txt file.\n";
    }
} else {
    echo "structure.txt file not found\n";
}