<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ReplaceRetiredOpenAiModel extends BaseMigration
{
    /**
     * OpenAI shuts down `gpt-5-mini` on 2026-12-11. The OpenAI provider stored that model in each user's
     * `ai_assistant` property, so the saved value is replaced with its successor `gpt-5.6-terra`.
     *
     * @var array<string, string>
     */
    private const REPLACEMENTS = [
        'gpt-5-mini' => 'gpt-5.6-terra',
        'gpt-5-mini-2025-08-07' => 'gpt-5.6-terra',
    ];

    /**
     * @return void
     */
    public function up(): void
    {
        $this->replaceModels(self::REPLACEMENTS);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->replaceModels(['gpt-5.6-terra' => 'gpt-5-mini']);
    }

    /**
     * Rewrites `ai_assistant.model` in users.properties according to the map.
     *
     * @param array<string, string> $map Old model => new model.
     * @return void
     */
    private function replaceModels(array $map): void
    {
        $rows = $this->fetchAll("SELECT id, properties FROM users WHERE properties LIKE '%ai_assistant%'");

        foreach ($rows as $row) {
            $properties = json_decode((string)$row['properties'], true);
            $model = $properties['ai_assistant']['model'] ?? null;
            if (!is_array($properties) || !is_string($model) || !isset($map[$model])) {
                continue;
            }

            $properties['ai_assistant']['model'] = $map[$model];
            $this->execute(
                'UPDATE users SET properties = ? WHERE id = ?',
                [json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $row['id']],
            );
        }
    }
}
