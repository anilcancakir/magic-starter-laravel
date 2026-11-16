<?php

namespace AnilcanCakir\MagicStarter\Tests;

use Symfony\Component\Yaml\Yaml;

/**
 * Guards the shape of the agent skill the package ships to host apps.
 *
 * Laravel Boost reads `SKILL.blade.php` before `SKILL.md`, and a host that is
 * not on Boost (an app that links the skill straight into `.claude/skills/`)
 * can only read the plain file. The skill is therefore one plain Markdown file:
 * a Blade template left beside it would win under Boost and drift from what
 * the host without Boost reads.
 */
final class BoostSkillTest extends TestCase
{
    private const SKILL_DIRECTORY = __DIR__ . '/../resources/boost/skills/magic-starter-development';

    public function test_the_skill_is_one_plain_markdown_file(): void
    {
        $this->assertFileExists(self::SKILL_DIRECTORY . '/SKILL.md');
        $this->assertFileDoesNotExist(self::SKILL_DIRECTORY . '/SKILL.blade.php');
    }

    public function test_the_frontmatter_names_the_skill_and_says_when_to_use_it(): void
    {
        $frontmatter = $this->frontmatter();

        $this->assertSame('magic-starter-development', $frontmatter['name'] ?? null);
        $this->assertNotEmpty($frontmatter['description'] ?? null);
        $this->assertNotEmpty($frontmatter['when_to_use'] ?? null);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', (string) ($frontmatter['version'] ?? ''));
    }

    public function test_the_body_carries_no_blade_directive(): void
    {
        $body = (string) file_get_contents(self::SKILL_DIRECTORY . '/SKILL.md');

        $this->assertDoesNotMatchRegularExpression('/^@(php|boostsnippet|endboostsnippet)\b/m', $body);
        $this->assertStringNotContainsString('{{', $body);
    }

    public function test_every_code_fence_is_closed(): void
    {
        $body = (string) file_get_contents(self::SKILL_DIRECTORY . '/SKILL.md');

        // A fence opened and never closed swallows every heading after it.
        $this->assertSame(0, preg_match_all('/^```/m', $body) % 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function frontmatter(): array
    {
        $body = (string) file_get_contents(self::SKILL_DIRECTORY . '/SKILL.md');

        $this->assertSame(1, preg_match('/\A---\n(.*?)\n---\n/s', $body, $matches), 'The skill has no frontmatter.');

        return Yaml::parse($matches[1]);
    }
}
