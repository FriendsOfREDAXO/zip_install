<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/zip_install.php';

use FriendsOfRedaxo\ZipInstall\ZipInstall;

/**
 * Lightweight test runner for installer_ignore matching.
 *
 * Runs without REDAXO bootstrap by instantiating ZipInstall without constructor,
 * then invoking the private matcher via reflection.
 */
final class InstallerIgnoreMatchTest
{
    private object $instance;
    private ReflectionMethod $matcher;

    public function __construct()
    {
        $reflectionClass = new ReflectionClass(ZipInstall::class);
        $this->instance = $reflectionClass->newInstanceWithoutConstructor();

        $this->matcher = $reflectionClass->getMethod('matchesInstallerIgnore');
        $this->matcher->setAccessible(true);
    }

    /**
     * @return list<array{0:string,1:bool,2:list<string>,3:bool,4:string}>
     */
    private function getCases(): array
    {
        return [
            ['.github/workflows/release.yml', true, ['.github'], true, 'Plain name should match nested directory basename'],
            ['tests/unit/MyTest.php', false, ['tests/**'], true, 'Recursive directory pattern should match files below tests'],
            ['docs/guide.md', false, ['docs/*.md'], true, 'Single-level glob should match markdown file in docs root'],
            ['docs/sub/guide.md', false, ['docs/*.md'], true, 'fnmatch semantics currently allow nested docs path for docs/*.md'],
            ['assets/app.js', false, ['docs/*.md', '.github'], false, 'Unrelated paths must not match'],
            ['package.yml', false, ['package.yml', '**/package.yml'], false, 'package.yml must never be removed'],
            ['src/.gitignore', false, ['.gitignore'], true, 'Plain file name should match nested basename'],
            ['foo/bar/baz.txt', false, ['foo/bar/**'], true, 'Recursive path prefix should match nested content'],
        ];
    }

    public function run(): int
    {
        $failed = 0;
        $cases = $this->getCases();

        foreach ($cases as $index => [$relativePath, $isDir, $patterns, $expected, $label]) {
            $actual = (bool) $this->matcher->invoke($this->instance, $relativePath, $isDir, $patterns);
            $ok = ($actual === $expected);

            if ($ok) {
                echo '[OK]   #' . ($index + 1) . ' ' . $label . PHP_EOL;
                continue;
            }

            $failed++;
            echo '[FAIL] #' . ($index + 1) . ' ' . $label . PHP_EOL;
            echo '       path=' . $relativePath . ', isDir=' . ($isDir ? 'true' : 'false') . PHP_EOL;
            echo '       patterns=' . json_encode($patterns, JSON_UNESCAPED_SLASHES) . PHP_EOL;
            echo '       expected=' . ($expected ? 'true' : 'false') . ', actual=' . ($actual ? 'true' : 'false') . PHP_EOL;
        }

        echo PHP_EOL;
        if (0 === $failed) {
            echo 'All installer_ignore matcher tests passed.' . PHP_EOL;
            return 0;
        }

        echo $failed . ' test(s) failed.' . PHP_EOL;
        return 1;
    }
}

$runner = new InstallerIgnoreMatchTest();
exit($runner->run());
