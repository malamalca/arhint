<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\ProjectsDashboard;
use Projects\Model\Entity\Project;

class ProjectsDashboardTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::createRouteBuilder('/')->scope('/projects', ['plugin' => 'Projects'], function ($routes): void {
            $routes->fallbacks();
        });
    }

    /**
     * @return \Projects\Model\Entity\Project
     */
    private function project(): Project
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', "**Gradnja** poteka po [načrtu](http://example.com).\n\n- <script>x</script>točka");
        $doc->addItem('warnings', 'Manjka poročilo', ['severity' => 'high']);
        $doc->addItem('open_questions', 'Kdo plača priključek?');
        $doc->addItem('deadlines', 'Poznejši rok', ['date' => '2026-12-01']);
        $doc->addItem('deadlines', 'Dopolnitev vloge', ['date' => '2026-11-15']);
        $doc->addItem('deadlines', 'Opravljen rok', ['date' => '2026-01-01', 'status' => 'done']);

        return new Project([
            'id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'no' => '2026-01',
            'title' => 'Hiša <b>Novak</b>',
            'status_id' => null,
            'descript' => $doc->toMarkdown(),
            'last_log' => (object)['created' => new DateTime('2026-10-20 10:00:00'), 'descript' => '<p>Sestanek z <b>investitorjem</b></p>'],
        ]);
    }

    /**
     * @return void
     */
    public function testCardShowsOverviewAndEscapesContent(): void
    {
        $html = ProjectsDashboard::card($this->project(), 'V teku', new Date('2026-11-01'));

        $this->assertStringContainsString('class="project-avatar"', $html);
        $this->assertStringContainsString('2026-01', $html);
        $this->assertStringContainsString('Hiša &lt;b&gt;Novak&lt;/b&gt;', $html);
        $this->assertStringContainsString('V teku', $html);
        // the header holds the avatar, the number, the title and the status, in this order
        $this->assertStringContainsString('projects-overview-header', $html);
        $this->assertLessThan(strpos($html, '2026-01'), strpos($html, 'project-avatar'));
        $this->assertLessThan(strpos($html, 'V teku'), strpos($html, 'Hiša'));
        $this->assertLessThan(strpos($html, 'Gradnja poteka'), strpos($html, 'V teku'));
        $this->assertStringContainsString('Gradnja poteka po načrtu.', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('1 odprtih opozoril', $html);
        $this->assertStringContainsString('1 odprtih vprašanj', $html);
        $this->assertStringContainsString('Dopolnitev vloge', $html);
        $this->assertStringNotContainsString('Poznejši rok', $html);
        $this->assertStringNotContainsString('Opravljen rok', $html);
        $this->assertStringContainsString('Sestanek z investitorjem', $html);
        $this->assertStringNotContainsString('--md-sys-color-error', $html);
    }

    /**
     * @return void
     */
    public function testStatusReplacesSummaryExcerpt(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('status', 'Čakamo na <i>gradbeno</i> dovoljenje.');
        $doc->setText('summary', 'Dolg povzetek projekta.');
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'P', 'descript' => $doc->toMarkdown()]);

        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));

        $this->assertStringContainsString('<b>Čakamo na gradbeno dovoljenje.</b>', $html);
        $this->assertStringNotContainsString('Dolg povzetek', $html);
    }

    /**
     * @return void
     */
    public function testDeadlinesDueWithinSevenDaysAreHighlighted(): void
    {
        $doc = KbDocument::empty();
        $doc->addItem('deadlines', 'Jutrišnji rok', ['date' => '2026-11-02']);
        $doc->addItem('deadlines', 'Rok čez teden', ['date' => '2026-11-08']);
        $doc->addItem('deadlines', 'Rok čez osem dni', ['date' => '2026-11-09']);
        $doc->addItem('deadlines', 'Zamujen rok', ['date' => '2026-10-30']);
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'P', 'descript' => $doc->toMarkdown()]);

        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));

        $this->assertStringContainsString('Zamujen rok', $html);
        $this->assertStringContainsString('zamuja 2 dni', $html);
        $this->assertStringContainsString('Jutrišnji rok', $html);
        $this->assertStringContainsString('jutri', $html);
        $this->assertStringContainsString('Rok čez teden', $html);
        $this->assertStringContainsString('čez 7 dni', $html);
        $this->assertStringNotContainsString('Rok čez osem dni', $html);
        $this->assertSame(3, substr_count($html, 'projects-overview-deadline'));
        $this->assertLessThan(strpos($html, 'Jutrišnji rok'), strpos($html, 'Zamujen rok'));
    }

    /**
     * @return void
     */
    public function testDeadlinesBeyondAMonthAreNotShown(): void
    {
        $doc = KbDocument::empty();
        $doc->addItem('deadlines', 'Rok čez mesec in dan', ['date' => '2026-12-02']);
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'P', 'descript' => $doc->toMarkdown()]);

        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        $this->assertStringNotContainsString('Rok čez mesec in dan', $html);
        $this->assertStringNotContainsString('projects-overview-deadline', $html);

        // exactly a month ahead is still shown, as the nearest deadline
        $doc->addItem('deadlines', 'Rok čez mesec', ['date' => '2026-12-01']);
        $project->descript = $doc->toMarkdown();
        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        $this->assertStringContainsString('Rok čez mesec', $html);
        $this->assertStringNotContainsString('Rok čez mesec in dan', $html);
    }

    /**
     * @return void
     */
    public function testDeadlineDueTodayAndConfiguredLimit(): void
    {
        $doc = KbDocument::empty();
        $doc->addItem('deadlines', 'Danes', ['date' => '2026-11-01']);
        $doc->addItem('deadlines', 'Čez dva dni', ['date' => '2026-11-03']);
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'P', 'descript' => $doc->toMarkdown()]);

        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        $this->assertStringContainsString('danes', $html);
        $this->assertStringContainsString('Čez dva dni', $html);

        Configure::write('Projects.Dashboard.deadlineDays', 0);
        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        Configure::delete('Projects.Dashboard.deadlineDays');
        $this->assertStringContainsString('Danes', $html);
        $this->assertStringNotContainsString('Čez dva dni', $html);
    }

    /**
     * @return void
     */
    public function testHeaderIsTintedWithInverseColorOfProject(): void
    {
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'P', 'colorize' => '#336699']);
        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        $this->assertStringContainsString('color-mix(in srgb, #cc9966 14%', $html);
        $this->assertStringNotContainsString('#336699', $html);

        // anything that is not a hex color must not reach the style attribute
        $project->colorize = 'red;" onload="x';
        $html = ProjectsDashboard::card($project, '', new Date('2026-11-01'));
        $this->assertStringNotContainsString('onload', $html);
        $this->assertMatchesRegularExpression('/color-mix\(in srgb, #[0-9a-f]{6} 14%/', $html);
    }

    /**
     * @return void
     */
    public function testOverdueDeadlineIsHighlighted(): void
    {
        $html = ProjectsDashboard::card($this->project(), '', new Date('2026-11-20'));

        $this->assertStringContainsString('--md-sys-color-error', $html);
    }

    /**
     * @return void
     */
    public function testCardWithoutKnowledgeBase(): void
    {
        $project = new Project(['id' => '4dd53305-9715-4be4-b169-20defe113d2a', 'no' => '1', 'title' => 'Prazen', 'descript' => 'Navaden opis']);
        $html = ProjectsDashboard::cards([$project], []);

        $this->assertStringContainsString('Prazen', $html);
        $this->assertStringNotContainsString('Navaden opis', $html);
    }
}
