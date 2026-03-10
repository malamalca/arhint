<?php
declare(strict_types=1);

namespace App\Test\TestCase\Filter;

use Cake\TestSuite\TestCase;

/**
 * App\Filter\Filter Test Case
 */
class FilterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction / parseQuery
    // -------------------------------------------------------------------------

    /**
     * A blank query string produces empty fields and terms.
     *
     * @return void
     */
    public function testEmptyQuery(): void
    {
        $f = new TestFilter('');
        $this->assertEquals('', $f->getQueryString());
        $this->assertEmpty($f->getFields()['fields']);
        $this->assertEmpty($f->getFields()['terms']);
    }

    /**
     * key:value pairs are parsed into fields.
     *
     * @return void
     */
    public function testKeyValueParsing(): void
    {
        $f = new TestFilter('status:open kind:bug');
        $this->assertEquals('open', $f->get('status'));
        $this->assertEquals('bug', $f->get('kind'));
    }

    /**
     * Quoted values containing spaces are preserved as single fields.
     *
     * @return void
     */
    public function testQuotedValueParsing(): void
    {
        $f = new TestFilter('status:"in progress"');
        $this->assertEquals('in progress', $f->get('status'));
    }

    /**
     * Bare words end up as terms.
     *
     * @return void
     */
    public function testTermsParsing(): void
    {
        $f = new TestFilter('myterm');
        $fields = $f->getFields();
        $this->assertContains('myterm', $fields['terms']);
    }

    // -------------------------------------------------------------------------
    // get / delete
    // -------------------------------------------------------------------------

    /**
     * get() returns null for missing fields.
     *
     * @return void
     */
    public function testGetMissingKey(): void
    {
        $f = new TestFilter('status:closed');
        $this->assertNull($f->get('nonexistent'));
    }

    /**
     * delete() removes a field from the internal map.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $f = new TestFilter('status:open kind:bug');
        $f->delete('status');
        $this->assertNull($f->get('status'));
        $this->assertEquals('bug', $f->get('kind'));
    }

    /**
     * delete() on a non-existent field is a no-op.
     *
     * @return void
     */
    public function testDeleteMissingKey(): void
    {
        $f = new TestFilter('status:open');
        $f->delete('nonexistent'); // no exception
        $this->assertEquals('open', $f->get('status'));
    }

    // -------------------------------------------------------------------------
    // check / checkRight / checkLeft
    // -------------------------------------------------------------------------

    /**
     * check() does a case-insensitive equality test.
     *
     * @return void
     */
    public function testCheck(): void
    {
        $f = new TestFilter('status:OPEN');
        $this->assertTrue($f->check('status', 'open'));
        $this->assertTrue($f->check('status', 'Open'));
        $this->assertFalse($f->check('status', 'closed'));
        $this->assertFalse($f->check('kind', 'bug')); // missing field
    }

    /**
     * checkRight() matches the right (suffix) of the field value.
     *
     * @return void
     */
    public function testCheckRight(): void
    {
        $f = new TestFilter('status:progress');
        $this->assertTrue($f->checkRight('status', 'ress'));
        $this->assertFalse($f->checkRight('status', 'prog'));
    }

    /**
     * checkLeft() matches the left (prefix) of the field value.
     *
     * @return void
     */
    public function testCheckLeft(): void
    {
        $f = new TestFilter('status:progress');
        $this->assertTrue($f->checkLeft('status', 'prog'));
        $this->assertFalse($f->checkLeft('status', 'ress'));
    }

    // -------------------------------------------------------------------------
    // getValidFields / addField
    // -------------------------------------------------------------------------

    /**
     * Fields added during initialize() are returned by getValidFields().
     *
     * @return void
     */
    public function testGetValidFields(): void
    {
        $f = new TestFilter('');
        $this->assertContains('status', $f->getValidFields());
        $this->assertContains('kind', $f->getValidFields());
    }

    // -------------------------------------------------------------------------
    // buildQuery
    // -------------------------------------------------------------------------

    /**
     * buildQuery() adds a new key:value pair.
     *
     * @return void
     */
    public function testBuildQueryAdd(): void
    {
        $f = new TestFilter('status:open');
        $result = $f->buildQuery('kind', 'bug');
        $this->assertStringContainsString('status:open', $result);
        $this->assertStringContainsString('kind:bug', $result);
    }

    /**
     * buildQuery() updates an existing key.
     *
     * @return void
     */
    public function testBuildQueryUpdate(): void
    {
        $f = new TestFilter('status:open');
        $result = $f->buildQuery('status', 'closed');
        $this->assertStringContainsString('status:closed', $result);
        $this->assertStringNotContainsString('status:open', $result);
    }

    /**
     * buildQuery() removes a key when null is passed as value.
     *
     * @return void
     */
    public function testBuildQueryRemove(): void
    {
        $f = new TestFilter('status:open kind:bug');
        $result = $f->buildQuery('status', null);
        $this->assertStringNotContainsString('status:open', $result);
        $this->assertStringContainsString('kind:bug', $result);
    }

    // -------------------------------------------------------------------------
    // escapeQueryArgument
    // -------------------------------------------------------------------------

    /**
     * Values without spaces are returned as-is.
     *
     * @return void
     */
    public function testEscapeQueryArgumentSimple(): void
    {
        $this->assertEquals('value', TestFilter::escapeQueryArgument('value'));
    }

    /**
     * Values containing spaces are wrapped in double quotes.
     *
     * @return void
     */
    public function testEscapeQueryArgumentWithSpaces(): void
    {
        $result = TestFilter::escapeQueryArgument('in progress');
        $this->assertEquals('"in progress"', $result);
    }

    // -------------------------------------------------------------------------
    // getErrors
    // -------------------------------------------------------------------------

    /**
     * A valid query string produces no errors.
     *
     * @return void
     */
    public function testGetErrorsValid(): void
    {
        $f = new TestFilter('status:open');
        $this->assertEmpty($f->getErrors());
    }
}
