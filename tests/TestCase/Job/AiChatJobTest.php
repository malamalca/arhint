<?php
declare(strict_types=1);

namespace App\Test\TestCase\Job;

use App\Job\AiChatJob;
use Cake\TestSuite\TestCase;

class AiChatJobTest extends TestCase
{
    public function testMarkdownIsConvertedToHtml(): void
    {
        $html = AiChatJob::renderMarkdown("Text with **bold**\n\n- one\n- two");

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<li>one</li>', $html);
    }

    public function testHtmlReplyIsShownAsCodeAndNotLost(): void
    {
        $html = AiChatJob::renderMarkdown("<p>Povzetek dokumenta</p>\n<p>TODO: preveriti</p>");

        $this->assertStringContainsString('<pre><code class="language-html">', $html);
        $this->assertStringContainsString('&lt;p&gt;Povzetek dokumenta&lt;/p&gt;', $html);
        $this->assertStringContainsString('TODO: preveriti', $html);
        $this->assertStringNotContainsString('<p>Povzetek', $html);
    }

    public function testInlineHtmlIsEscapedNotRendered(): void
    {
        $html = AiChatJob::renderMarkdown('Click <script>alert(1)</script> here');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testNonEmptyReplyNeverBecomesEmpty(): void
    {
        $this->assertNotSame('', AiChatJob::renderMarkdown('<!-- comment -->'));
    }
}
