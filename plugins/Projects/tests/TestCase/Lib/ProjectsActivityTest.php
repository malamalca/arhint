<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\ProjectsActivity;
use Projects\Model\Entity\Project;

class ProjectsActivityTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int, string>
     */
    public array $fixtures = ['app.Users', 'app.Logs', 'plugin.Projects.Projects'];

    /**
     * @return void
     */
    public function testDaysAndLevel(): void
    {
        $today = new Date('2026-11-01');

        $this->assertSame(0, ProjectsActivity::days(new DateTime('2026-11-01 23:59:00'), $today));
        $this->assertSame(1, ProjectsActivity::days(new DateTime('2026-10-31 00:01:00'), $today));
        $this->assertNull(ProjectsActivity::days(null, $today));

        $this->assertSame(ProjectsActivity::RECENT, ProjectsActivity::level(29));
        $this->assertSame(ProjectsActivity::QUIET, ProjectsActivity::level(30));
        $this->assertSame(ProjectsActivity::QUIET, ProjectsActivity::level(89));
        $this->assertSame(ProjectsActivity::DORMANT, ProjectsActivity::level(90));
        $this->assertSame(ProjectsActivity::DORMANT, ProjectsActivity::level(null));

        Configure::write('Projects.Dashboard', ['quietDays' => 5, 'dormantDays' => 10]);
        $this->assertSame(ProjectsActivity::QUIET, ProjectsActivity::level(5));
        $this->assertSame(ProjectsActivity::DORMANT, ProjectsActivity::level(10));
        Configure::delete('Projects.Dashboard');
    }

    /**
     * @return void
     */
    public function testWeeklyCountsEntriesPerWeekWithLastWeekLast(): void
    {
        $today = new Date('2026-11-01');
        $logs = $this->getTableLocator()->get('App.Logs');
        $logs->deleteAll([]);
        foreach (['2026-11-01 08:00:00', '2026-10-26 08:00:00', '2026-10-25 08:00:00', '2026-08-10 08:00:00', '2026-01-01 08:00:00'] as $created) {
            $log = $logs->newEntity([
                'id' => Text::uuid(),
                'model' => 'Project',
                'foreign_id' => self::PROJECT,
                'user_id' => USER_ADMIN,
                'action' => 'Comment',
                'descript' => 'x',
            ]);
            $log->created = new DateTime($created);
            $log->modified = new DateTime($created);
            $logs->saveOrFail($log, ['checkRules' => false]);
        }

        $weeks = ProjectsActivity::weekly([self::PROJECT, 'other'], $today);

        $this->assertCount(ProjectsActivity::WEEKS, $weeks[self::PROJECT]);
        $this->assertSame(2, $weeks[self::PROJECT][ProjectsActivity::WEEKS - 1]);
        $this->assertSame(1, $weeks[self::PROJECT][ProjectsActivity::WEEKS - 2]);
        $this->assertSame(1, $weeks[self::PROJECT][0]);
        $this->assertSame(4, array_sum($weeks[self::PROJECT]));
        $this->assertSame(0, array_sum($weeks['other']));
        $this->assertSame([], ProjectsActivity::weekly([], $today));
    }

    /**
     * @return void
     */
    public function testHtmlShowsLevelAndChart(): void
    {
        $today = new Date('2026-11-01');
        $weeks = array_fill(0, ProjectsActivity::WEEKS, 0);
        $weeks[11] = 3;

        $recent = new Project(['id' => 'a', 'last_log' => (object)['created' => new DateTime('2026-10-30 10:00:00')]]);
        $html = ProjectsActivity::html($recent, $today, $weeks);
        $this->assertStringContainsString('pred 2 dnevi', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertSame(ProjectsActivity::WEEKS, substr_count($html, '<rect'));
        $this->assertStringNotContainsString('V mirovanju', $html);
        $this->assertSame('', ProjectsActivity::footer($recent, $today));

        $dormant = new Project(['id' => 'b', 'last_log' => (object)['created' => new DateTime('2026-06-01 10:00:00')]]);
        $this->assertStringNotContainsString('V mirovanju', ProjectsActivity::html($dormant, $today, $weeks));
        $footer = ProjectsActivity::footer($dormant, $today);
        $this->assertStringContainsString('projects-overview-footer', $footer);
        $this->assertStringContainsString('V mirovanju', $footer);
        $this->assertStringContainsString('že 153 dni brez sprememb', $footer);

        $none = new Project(['id' => 'c', 'created' => new DateTime('2026-10-30 10:00:00')]);
        $html = ProjectsActivity::html($none, $today);
        $this->assertStringContainsString('še brez zapisov', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }
}
