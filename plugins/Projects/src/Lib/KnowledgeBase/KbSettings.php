<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use Cake\Core\Configure;

/**
 * Which companies the AI features of the knowledge base are for.
 *
 * `Projects.KnowledgeBase.companies` lists the ids of the companies (`projects.owner_id`, the company of the users
 * of the project). Empty means every company. It limits what the AI does: the periodic update, the regeneration,
 * the conversation and its tools. Reading, editing by hand, history and the calendar work on the knowledge bases
 * that exist, whatever the company.
 */
class KbSettings
{
    /**
     * Ids of the companies the AI features are limited to; empty when they are not limited.
     *
     * @return array<int, string>
     */
    public static function companies(): array
    {
        $companies = (array)Configure::read('Projects.KnowledgeBase.companies');

        return array_values(array_filter(array_map('strval', $companies), fn(string $id): bool => $id !== ''));
    }

    /**
     * Whether the AI features are on for the company.
     *
     * @param string|null $companyId Company of the project (`projects.owner_id`).
     * @return bool
     */
    public static function companyAllowed(?string $companyId): bool
    {
        $companies = self::companies();

        return $companies === [] || ($companyId !== null && in_array($companyId, $companies, true));
    }
}
