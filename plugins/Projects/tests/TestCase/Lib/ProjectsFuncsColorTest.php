<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Projects\Lib\ProjectsFuncs;
use Projects\Model\Entity\Project;

class ProjectsFuncsColorTest extends TestCase
{
    /**
     * @return void
     */
    public function testColorOfProjectIsKept(): void
    {
        $project = new Project(['no' => '2022-01', 'title' => 'P', 'colorize' => '#336699']);

        $this->assertSame('#336699', ProjectsFuncs::colorFor($project));
    }

    /**
     * @return void
     */
    public function testColorIsMadeFromNumberWhenEmptyOrInvalid(): void
    {
        $colors = [];
        foreach (['2022-01', '2022-02', '2022-03', '2022-04'] as $no) {
            $project = new Project(['no' => $no, 'title' => 'P', 'colorize' => null]);
            $color = ProjectsFuncs::colorFor($project);

            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $color);
            $this->assertSame($color, ProjectsFuncs::colorFor($project), 'The same number gives the same color.');
            $colors[] = $color;
        }
        $this->assertCount(4, array_unique($colors), 'Consecutive numbers give different colors.');

        // black is what the color input submits when no color was picked
        foreach (['red', '#000000'] as $notSet) {
            $project = new Project(['no' => '2022-01', 'title' => 'P', 'colorize' => $notSet]);
            $this->assertSame($colors[0], ProjectsFuncs::colorFor($project));
        }
    }

    /**
     * @return void
     */
    public function testColorForNumberWithoutDigitsUsesTitle(): void
    {
        $project = new Project(['no' => 'ABC', 'title' => 'Hiša Novak']);

        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', ProjectsFuncs::colorFor($project));
    }

    /**
     * @return void
     */
    public function testGeneratedColorsAreNeitherTooLightNorTooDark(): void
    {
        for ($n = 1; $n <= 200; $n++) {
            $color = ProjectsFuncs::colorFor(new Project(['no' => sprintf('2026-%03d', $n), 'title' => 'P']));
            $luma = (0.299 * hexdec(substr($color, 1, 2)) + 0.587 * hexdec(substr($color, 3, 2))
                + 0.114 * hexdec(substr($color, 5, 2))) / 255;

            $this->assertGreaterThan(0.15, $luma, $color);
            $this->assertLessThan(0.65, $luma, $color);
        }
    }

    /**
     * @return void
     */
    public function testThumbOfProjectWithoutColorStillRenders(): void
    {
        $png = ProjectsFuncs::thumb(new Project(['no' => '2022-01', 'title' => 'Hiša Novak']), 40);

        $this->assertStringStartsWith("\x89PNG", (string)$png);
    }
}
