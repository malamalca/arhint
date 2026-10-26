<?php
declare(strict_types=1);

namespace Projects\Lib;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use DateTimeImmutable;
use DateTimeInterface;
use Projects\Model\Entity\Project;

/**
 * How lively a project is: the time since its last change, a small chart of the entries per week
 * and a flag for projects where nothing has happened for a long time.
 *
 * A change is an entry in the log of the project; saving a document of the project writes one too.
 * Limits are set with `Projects.Dashboard.quietDays` (default 30) and `Projects.Dashboard.dormantDays` (default 90).
 */
class ProjectsActivity
{
    public const RECENT = 'recent';
    public const QUIET = 'quiet';
    public const DORMANT = 'dormant';

    /** Number of weeks the chart shows. */
    public const WEEKS = 12;

    /**
     * Whole days from the day of the last change to today.
     *
     * @param \DateTimeInterface|null $last Time of the last change.
     * @param \Cake\I18n\Date $today Current day.
     * @return int|null Null when there is no known change.
     */
    public static function days(?DateTimeInterface $last, Date $today): ?int
    {
        if ($last === null) {
            return null;
        }

        $from = new DateTimeImmutable($last->format('Y-m-d'));
        $to = new DateTimeImmutable($today->format('Y-m-d'));

        return max(0, (int)$from->diff($to)->format('%r%a'));
    }

    /**
     * Level of activity for the days since the last change.
     *
     * @param int|null $days Days since the last change, null when it is not known.
     * @return string One of RECENT, QUIET, DORMANT.
     */
    public static function level(?int $days): string
    {
        $quiet = (int)Configure::read('Projects.Dashboard.quietDays', 30);
        $dormant = (int)Configure::read('Projects.Dashboard.dormantDays', 90);

        return match (true) {
            $days === null || $days >= $dormant => self::DORMANT,
            $days >= $quiet => self::QUIET,
            default => self::RECENT,
        };
    }

    /**
     * Entries per week of the log of projects, the last week last.
     *
     * @param array<int, string> $projectIds Ids of projects.
     * @param \Cake\I18n\Date $today Current day.
     * @return array<string, array<int, int>> Counts indexed by project id; every project has WEEKS counts.
     */
    public static function weekly(array $projectIds, Date $today): array
    {
        $weeks = array_fill_keys($projectIds, array_fill(0, self::WEEKS, 0));
        if ($projectIds === []) {
            return $weeks;
        }

        $since = $today->subDays(self::WEEKS * 7 - 1);
        $logs = TableRegistry::getTableLocator()->get('App.Logs')->find()
            ->select(['foreign_id', 'created'])
            ->where([
                'model' => 'Project',
                'foreign_id IN' => $projectIds,
                'created >=' => $since->format('Y-m-d') . ' 00:00:00',
            ])
            ->all();

        foreach ($logs as $log) {
            $age = self::days($log->created, $today);
            $week = self::WEEKS - 1 - intdiv((int)$age, 7);
            if (isset($weeks[$log->foreign_id][$week])) {
                $weeks[$log->foreign_id][$week]++;
            }
        }

        return $weeks;
    }

    /**
     * Html with the time of the last change, the flag and the chart.
     *
     * @param \Projects\Model\Entity\Project $project Project, with the last log if there is one.
     * @param \Cake\I18n\Date $today Current day.
     * @param array<int, int> $weeks Entries per week, see weekly().
     * @return string
     */
    public static function html(Project $project, Date $today, array $weeks = []): string
    {
        $last = $project->last_log->created ?? $project->created;
        $days = self::days($last, $today);
        $level = self::level($days);

        $color = match ($level) {
            self::RECENT => 'var(--md-sys-color-primary)',
            self::QUIET => 'var(--md-sys-color-tertiary, #b26a00)',
            default => 'var(--md-sys-color-error)',
        };

        $when = match (true) {
            $project->last_log === null => __d('projects', 'no entries yet'),
            $days === 0 => __d('projects', 'today'),
            $days === 1 => __d('projects', 'yesterday'),
            default => __d('projects', '{0} days ago', $days),
        };

        $html = '<div class="projects-activity" style="display: flex; align-items: center; gap: .5rem;">'
            . '<span style="width: .6rem; height: .6rem; border-radius: 50%; flex: none; background: '
            . $color . ';"></span>'
            . '<span class="small" style="flex: 1; min-width: 0;">' . h(__d('projects', 'Last change')) . ': '
            . '<b style="color: ' . $color . ';">' . h($when) . '</b></span>'
            . self::chart($weeks, $color) . '</div>';

        return $html;
    }

    /**
     * Footer of a card of a project where nothing has happened for a long time.
     *
     * @param \Projects\Model\Entity\Project $project Project, with the last log if there is one.
     * @param \Cake\I18n\Date $today Current day.
     * @return string Empty for a project that is not dormant.
     */
    public static function footer(Project $project, Date $today): string
    {
        $days = self::days($project->last_log->created ?? $project->created, $today);
        if (self::level($days) !== self::DORMANT) {
            return '';
        }

        $text = '<b>' . h(__d('projects', 'Dormant')) . '</b>';
        if ($project->last_log !== null) {
            $text .= ' &ndash; ' . h(__d('projects', 'nothing has changed for {0} days', $days));
        }

        return '<div class="projects-overview-footer small" style="padding: .5rem 1rem; '
            . 'border-top: solid 1px var(--md-sys-color-error); color: var(--md-sys-color-error); '
            . 'background: color-mix(in srgb, var(--md-sys-color-error) 10%, transparent);">' . $text . '</div>';
    }

    /**
     * Small bar chart of the entries per week.
     *
     * @param array<int, int> $weeks Entries per week, the last week last.
     * @param string $color Color of the bars.
     * @return string Svg, empty when there are no weeks.
     */
    private static function chart(array $weeks, string $color): string
    {
        if ($weeks === []) {
            return '';
        }

        $height = 20;
        $barWidth = 5;
        $gap = 2;
        $max = max(1, ...$weeks);
        $bars = '';
        foreach (array_values($weeks) as $i => $count) {
            $barHeight = $count === 0 ? 1 : max(3, (int)round($height * $count / $max));
            $bars .= sprintf(
                '<rect x="%d" y="%d" width="%d" height="%d" rx="1" fill="%s"%s/>',
                $i * ($barWidth + $gap),
                $height - $barHeight,
                $barWidth,
                $barHeight,
                $color,
                $count === 0 ? ' opacity=".25"' : '',
            );
        }

        return sprintf(
            '<svg role="img" width="%d" height="%d" viewBox="0 0 %1$d %2$d" style="flex: none;">'
            . '<title>%s</title>%s</svg>',
            count($weeks) * ($barWidth + $gap) - $gap,
            $height,
            h(__d('projects', 'Entries per week in the last {0} weeks', count($weeks))),
            $bars,
        );
    }
}
