<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Command\SendTestEmailCommand Test Case
 *
 * @uses \App\Command\SendTestEmailCommand
 */
class SendTestEmailCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * Test sending the test message
     *
     * @return void
     */
    public function testSend(): void
    {
        $this->exec('send_test_email someone@example.com --subject "Layout check"');

        $this->assertExitSuccess();
        $this->assertOutputContains('someone@example.com');
        $this->assertMailSentTo('someone@example.com');
        $this->assertMailSubjectContains('Layout check');
        $this->assertMailContainsHtml('This is a test message');
    }

    /**
     * Test an invalid recipient
     *
     * @return void
     */
    public function testInvalidRecipient(): void
    {
        $this->exec('send_test_email not-an-address');

        $this->assertExitError();
        $this->assertErrorContains('not a valid e-mail address');
        $this->assertNoMailSent();
    }
}
