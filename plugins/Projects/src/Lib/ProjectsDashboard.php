<?php
declare(strict_types=1);

namespace Projects\Lib;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\Routing\Router;
use DateTimeImmutable;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbItem;
use Projects\Model\Entity\Project;

/**
 * Cards with a short overview of a project, shown on the dashboard.
 */
class ProjectsDashboard
{
    private const SUMMARY_CHARS = 180;
    private const LOG_CHARS = 90;

    /** Deadlines up to this many days ahead are highlighted; see `Projects.Dashboard.deadlineDays`. */
    private const DEADLINE_DAYS = 7;

    /** Most deadlines shown on a card. */
    private const MAX_DEADLINES = 3;

    /**
     * Html of the grid with a card per project.
     *
     * @param iterable<\Projects\Model\Entity\Project> $projects Projects, with the last log if there is one.
     * @param array<string, string> $statuses Status titles indexed by status id.
     * @param \Cake\I18n\Date|null $today Current day, overridable for tests.
     * @param array<string, array<int, int>> $weekly Entries per week by project id, see ProjectsActivity::weekly().
     * @return string
     */
    public static function cards(iterable $projects, array $statuses, ?Date $today = null, array $weekly = []): string
    {
        $today ??= Date::today();

        $html = '';
        foreach ($projects as $project) {
            $html .= self::card(
                $project,
                $statuses[$project->status_id ?? ''] ?? '',
                $today,
                $weekly[$project->id] ?? [],
            );
        }

        return '<div class="projects-overview-cards" style="display: grid; gap: 1rem; padding: 0 1rem 1rem; '
            . 'grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">' . $html . '</div>';
    }

    /**
     * Html of a single card.
     *
     * @param \Projects\Model\Entity\Project $project Project.
     * @param string $status Status title.
     * @param \Cake\I18n\Date $today Current day.
     * @param array<int, int> $weeks Entries per week of the project for the chart.
     * @return string
     */
    public static function card(Project $project, string $status, Date $today, array $weeks = []): string
    {
        $url = Router::url(['plugin' => 'Projects', 'controller' => 'Projects', 'action' => 'view', $project->id]);

        $knowledge = KbDocument::isKnowledgeBase($project->descript)
            ? KbDocument::parse((string)$project->descript)
            : null;

        // The status (e.g. DGD) sits in the same column as the number and the title, so all three line up.
        $chip = $status === '' ? '' : '<div style="margin-top: .25rem;"><span class="chip" style="height: auto; '
            . 'margin: 0; padding: 1px 8px; font-size: .7rem; line-height: 1.4; border-radius: 10px; '
            . 'background: var(--md-sys-color-surface); color: var(--md-sys-color-on-surface);">'
            . h($status) . '</span></div>';

        // The header is a light tone of the inverse of the color of the project (made from its number when it has
        // none), so it stands out from the color of the initials in the avatar.
        $accent = self::inverse(ProjectsFuncs::colorFor($project));

        $head = '<div class="projects-overview-header" style="display: flex; gap: .75rem; align-items: center; '
            . 'padding: .75rem 1rem; color: var(--md-sys-color-on-surface); '
            . 'border-bottom: solid 1px color-mix(in srgb, ' . $accent . ' 25%, var(--md-sys-color-surface)); '
            . 'background: color-mix(in srgb, ' . $accent . ' 14%, var(--md-sys-color-surface));">'
            . '<img src="data:image/png;base64,' . base64_encode((string)ProjectsFuncs::thumb($project, 40))
            . '" alt="" class="project-avatar" style="height: 40px; width: 40px; flex: none;" />'
            . '<div style="min-width: 0;"><div class="small">' . h($project->no) . '</div>'
            . '<a href="' . h($url) . '" style="color: inherit;"><b>' . h($project->title) . '</b></a>'
            . $chip . '</div></div>';

        $body = '';

        if ($knowledge) {
            $state = self::plain($knowledge->text(KbDocument::STATUS), KbDocument::STATUS_MAX_CHARS);
            if ($state !== '') {
                $body .= '<p style="margin: .5rem 0;" title="' . h(__d('projects', 'Project status')) . '"><b>'
                    . h($state) . '</b></p>';
            } else {
                // projects whose status was not made yet
                $summary = self::plain($knowledge->text('summary'), self::SUMMARY_CHARS);
                if ($summary !== '') {
                    $body .= '<p style="margin: .5rem 0;">' . h($summary) . '</p>';
                }
            }
            $body .= self::indicators($knowledge, $today);
        }

        $body .= ProjectsActivity::html($project, $today, $weeks);

        if ($project->last_log) {
            $body .= '<div class="small" style="margin-top: .25rem; opacity: .8;">'
                . h($project->last_log->created->i18nFormat('d. MMM y')) . ' &ndash; '
                . h(self::plain((string)$project->last_log->descript, self::LOG_CHARS)) . '</div>';
        }

        return '<div class="projects-overview-card" style="display: flex; flex-direction: column; '
            . 'overflow: hidden; border: solid 1px var(--md-sys-color-outline-variant); '
            . 'background: color-mix(in srgb, var(--md-sys-color-on-surface) 4%, var(--md-sys-color-surface)); '
            . 'border-radius: var(--md-sys-shape-corner-medium);">'
            . $head . '<div style="flex: 1; padding: .25rem 1rem 1rem;">' . $body . '</div>'
            . ProjectsActivity::footer($project, $today) . '</div>';
    }

    /**
     * The inverse of a color.
     *
     * @param string $hex Color as #rrggbb.
     * @return string Color as #rrggbb.
     */
    private static function inverse(string $hex): string
    {
        return sprintf('#%06x', 0xFFFFFF - (int)hexdec(substr($hex, 1)));
    }

    /**
     * Open warnings, open questions and the nearest open deadline of the knowledge base.
     *
     * @param \Projects\Lib\KnowledgeBase\KbDocument $knowledge Knowledge base.
     * @param \Cake\I18n\Date $today Current day.
     * @return string
     */
    private static function indicators(KbDocument $knowledge, Date $today): string
    {
        $counts = $knowledge->openCounts();
        $html = '';

        $parts = [];
        if (!empty($counts['warnings'])) {
            $parts[] = h(__d('projects', '{0} open warnings', $counts['warnings']));
        }
        if (!empty($counts['open_questions'])) {
            $parts[] = h(__d('projects', '{0} open questions', $counts['open_questions']));
        }
        if ($parts) {
            $html .= '<div class="small" style="margin-top: .5rem;">' . implode(' &middot; ', $parts) . '</div>';
        }

        // Deadlines further away than a month are not shown on the card; overdue ones are.
        $horizon = $today->addMonths(1)->format('Y-m-d');
        $deadlines = array_filter(
            $knowledge->items('deadlines'),
            fn(KbItem $item): bool => $item->isOpen() && !empty($item->date) && (string)$item->date <= $horizon,
        );
        usort($deadlines, fn(KbItem $a, KbItem $b): int => strcmp((string)$a->date, (string)$b->date));

        // Overdue deadlines and the ones that fall due within the next days are all shown, the most urgent first;
        // when there are none, only the nearest deadline is.
        $soon = (int)Configure::read('Projects.Dashboard.deadlineDays', self::DEADLINE_DAYS);
        $urgent = array_filter(
            $deadlines,
            fn(KbItem $item): bool => self::daysUntil((string)$item->date, $today) <= $soon,
        );
        foreach ($urgent ?: array_slice($deadlines, 0, 1) as $i => $item) {
            if ($i >= self::MAX_DEADLINES) {
                break;
            }
            $html .= self::deadline($item, $today, $urgent !== []);
        }

        return $html;
    }

    /**
     * A line with a deadline; an overdue one is red, one that falls due soon is highlighted.
     *
     * @param \Projects\Lib\KnowledgeBase\KbItem $item Deadline.
     * @param \Cake\I18n\Date $today Current day.
     * @param bool $urgent Whether the line is one of the overdue or soon due deadlines.
     * @return string
     */
    private static function deadline(KbItem $item, Date $today, bool $urgent): string
    {
        $days = self::daysUntil((string)$item->date, $today);
        $date = Date::parse((string)$item->date)->i18nFormat('d. MMM y');

        $style = '';
        $when = '';
        if ($urgent) {
            $color = $days < 0 ? 'var(--md-sys-color-error)' : 'var(--md-sys-color-tertiary, #b26a00)';
            $style = ' color: ' . $color . '; border-left: 3px solid ' . $color . '; padding-left: .5rem;';
            $when = ' (' . h(match (true) {
                $days < -1 => __d('projects', '{0} days overdue', -$days),
                $days === -1 => __d('projects', '1 day overdue'),
                $days === 0 => __d('projects', 'due today'),
                $days === 1 => __d('projects', 'due tomorrow'),
                default => __d('projects', 'in {0} days', $days),
            }) . ')';
        }

        return '<div class="small projects-overview-deadline" style="margin-top: .25rem;' . $style . '">'
            . h($urgent ? __d('projects', 'Deadline') : __d('projects', 'Next deadline')) . ': <b>' . h($date) . '</b>'
            . $when . ' &ndash; ' . h(self::plain($item->text, self::LOG_CHARS)) . '</div>';
    }

    /**
     * Days from today to the date, negative when the date has passed.
     *
     * @param string $date Date, Y-m-d.
     * @param \Cake\I18n\Date $today Current day.
     * @return int
     */
    private static function daysUntil(string $date, Date $today): int
    {
        $to = new DateTimeImmutable($date);
        $from = new DateTimeImmutable($today->format('Y-m-d'));

        return (int)$from->diff($to)->format('%r%a');
    }

    /**
     * Plain one-line text cut to the length, without html and the most common markdown marks.
     *
     * @param string $text Markdown or html.
     * @param int $chars Maximum number of characters.
     * @return string
     */
    private static function plain(string $text, int $chars): string
    {
        $text = strip_tags($text);
        $text = (string)preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        $text = (string)preg_replace('/[#*_`>|]+/', '', $text);
        $text = trim((string)preg_replace('/\s+/u', ' ', html_entity_decode($text)));

        return mb_strimwidth($text, 0, $chars, '…');
    }
}
