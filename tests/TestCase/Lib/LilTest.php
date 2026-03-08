<?php
declare(strict_types=1);

namespace App\Test\TestCase\Lib;

use App\Lib\Lil;
use ArrayObject;
use Cake\TestSuite\TestCase;

/**
 * App\Lib\Lil Test Case
 */
class LilTest extends TestCase
{
    // -------------------------------------------------------------------------
    // insertIntoArray – no positional option (plain union)
    // -------------------------------------------------------------------------

    /**
     * With no option the element is union-merged at the end.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertNoOption(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['c' => 3]);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr);
    }

    /**
     * With no option, existing keys are NOT overwritten (union semantics).
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertNoOptionDoesNotOverwriteExistingKey(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['a' => 99, 'c' => 3]);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr);
    }

    // -------------------------------------------------------------------------
    // insertIntoArray – 'after' option
    // -------------------------------------------------------------------------

    /**
     * Insert after a middle key; string keys are preserved (default).
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertAfterPreservesKeys(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['after' => 'b']);
        $this->assertSame(['a' => 1, 'b' => 2, 'x' => 10, 'c' => 3], $arr);
    }

    /**
     * Insert after the last key appends the element.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertAfterLastKey(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['c' => 3], ['after' => 'b']);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr);
    }

    /**
     * With preserve=false, numeric element keys are re-indexed.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertAfterPreserveFalseReindexesNumericKeys(): void
    {
        $arr = [0 => 'a', 1 => 'b', 2 => 'c'];
        // Element has numeric key 5; with preserve=false it should be re-indexed
        Lil::insertIntoArray($arr, [5 => 'x'], ['after' => 1, 'preserve' => false]);
        $this->assertSame([0 => 'a', 1 => 'b', 2 => 'x', 3 => 'c'], $arr);
    }

    /**
     * With preserve=true (default), numeric element keys are kept as-is.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertAfterPreserveTrueKeepsNumericKeys(): void
    {
        $arr = [0 => 'a', 1 => 'b', 2 => 'c'];
        Lil::insertIntoArray($arr, [5 => 'x'], ['after' => 1]);
        $this->assertSame([0 => 'a', 1 => 'b', 5 => 'x', 2 => 'c'], $arr);
    }

    /**
     * When the 'after' key does not exist, the array is left unchanged.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertAfterKeyNotFoundLeavesArrayUnchanged(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['x' => 10], ['after' => 'z']);
        $this->assertSame(['a' => 1, 'b' => 2], $arr);
    }

    // -------------------------------------------------------------------------
    // insertIntoArray – 'before' option
    // -------------------------------------------------------------------------

    /**
     * Insert before a middle key; string keys are preserved (default).
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertBeforePreservesKeys(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['before' => 'b']);
        $this->assertSame(['a' => 1, 'x' => 10, 'b' => 2, 'c' => 3], $arr);
    }

    /**
     * Insert before the first key prepends the element.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertBeforeFirstKey(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['x' => 10], ['before' => 'a']);
        $this->assertSame(['x' => 10, 'a' => 1, 'b' => 2], $arr);
    }

    /**
     * With preserve=false the element key is re-indexed for numeric keys.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertBeforePreserveFalseReindexesNumericKeys(): void
    {
        $arr = [0 => 'a', 1 => 'b', 2 => 'c'];
        Lil::insertIntoArray($arr, [5 => 'x'], ['before' => 1, 'preserve' => false]);
        $this->assertSame([0 => 'a', 1 => 'x', 2 => 'b', 3 => 'c'], $arr);
    }

    /**
     * When the 'before' key does not exist, the array is left unchanged.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertBeforeKeyNotFoundLeavesArrayUnchanged(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        Lil::insertIntoArray($arr, ['x' => 10], ['before' => 'z']);
        $this->assertSame(['a' => 1, 'b' => 2], $arr);
    }

    // -------------------------------------------------------------------------
    // insertIntoArray – 'replace' option
    // -------------------------------------------------------------------------

    /**
     * Replace a middle key: the original key is removed and the new element
     * is inserted in its place; string keys of element are preserved.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testReplacePreservesKeys(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['replace' => 'b']);
        $this->assertSame(['a' => 1, 'x' => 10, 'c' => 3], $arr);
        $this->assertArrayNotHasKey('b', $arr);
    }

    /**
     * Replace with preserve=false re-indexes numeric element keys.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testReplacePreserveFalse(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['replace' => 'b', 'preserve' => false]);
        $this->assertSame(['a' => 1, 'x' => 10, 'c' => 3], $arr);
        $this->assertArrayNotHasKey('b', $arr);
    }

    /**
     * Replace the first key.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testReplaceFirstKey(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['replace' => 'a']);
        $this->assertSame(['x' => 10, 'b' => 2, 'c' => 3], $arr);
        $this->assertArrayNotHasKey('a', $arr);
    }

    /**
     * Replace the last key.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testReplaceLastKey(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        Lil::insertIntoArray($arr, ['x' => 10], ['replace' => 'c']);
        $this->assertSame(['a' => 1, 'b' => 2, 'x' => 10], $arr);
        $this->assertArrayNotHasKey('c', $arr);
    }

    // -------------------------------------------------------------------------
    // insertIntoArray – ArrayObject support
    // -------------------------------------------------------------------------

    /**
     * The method accepts an ArrayObject and modifies it in place.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertIntoArrayObject(): void
    {
        $obj = new ArrayObject(['a' => 1, 'b' => 2, 'c' => 3]);
        Lil::insertIntoArray($obj, ['x' => 10], ['after' => 'b']);
        $this->assertSame(['a' => 1, 'b' => 2, 'x' => 10, 'c' => 3], $obj->getArrayCopy());
    }

    /**
     * ArrayObject 'before' option works correctly.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testInsertBeforeIntoArrayObject(): void
    {
        $obj = new ArrayObject(['a' => 1, 'b' => 2, 'c' => 3]);
        Lil::insertIntoArray($obj, ['x' => 10], ['before' => 'b']);
        $this->assertSame(['a' => 1, 'x' => 10, 'b' => 2, 'c' => 3], $obj->getArrayCopy());
    }

    /**
     * ArrayObject 'replace' option works correctly.
     *
     * @return void
     * @uses \App\Lib\Lil::insertIntoArray()
     */
    public function testReplaceIntoArrayObject(): void
    {
        $obj = new ArrayObject(['a' => 1, 'b' => 2, 'c' => 3]);
        Lil::insertIntoArray($obj, ['x' => 10], ['replace' => 'b']);
        $result = $obj->getArrayCopy();
        $this->assertSame(['a' => 1, 'x' => 10, 'c' => 3], $result);
        $this->assertArrayNotHasKey('b', $result);
    }
}
