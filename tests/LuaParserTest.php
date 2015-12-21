<?php
/**
 * LuaParserTest.php
 *
 * @author Koen Vlaswinkel <koen@vlaswinkel.info>
 * @since  21/12/2015 17:30
 */

namespace Vlaswinkel\Lua\Tests;

use Vlaswinkel\Lua\AST\NilASTNode;
use Vlaswinkel\Lua\AST\NumberASTNode;
use Vlaswinkel\Lua\AST\StringASTNode;
use Vlaswinkel\Lua\AST\TableASTNode;
use Vlaswinkel\Lua\LuaInputStream;
use Vlaswinkel\Lua\LuaParser;
use Vlaswinkel\Lua\LuaTokenStream;

class LuaParserTest extends \PHPUnit_Framework_TestCase {
    public function testString() {
        $parser = new LuaParser(new LuaTokenStream(new LuaInputStream('"foo"')));

        $node = $parser->parse();

        $this->assertEquals(StringASTNode::NAME, $node->getName());
        $this->assertInstanceOf(StringASTNode::class, $node);
        $this->assertEquals("foo", $node->getValue());
    }

    public function testNumber() {
        $parser = new LuaParser(new LuaTokenStream(new LuaInputStream('1337')));

        $node = $parser->parse();

        $this->assertEquals(NumberASTNode::NAME, $node->getName());
        $this->assertInstanceOf(NumberASTNode::class, $node);
        $this->assertEquals(1337, $node->getValue());
    }

    public function testNil() {
        $parser = new LuaParser(new LuaTokenStream(new LuaInputStream('nil')));

        $node = $parser->parse();

        $this->assertEquals(NilASTNode::NAME, $node->getName());
        $this->assertInstanceOf(NilASTNode::class, $node);
    }

    public function testTableKey() {
        $parser = new LuaParser(new LuaTokenStream(new LuaInputStream('["test"]')));

        $node = $parser->parse();

        $this->assertEquals(StringASTNode::NAME, $node->getName());
        $this->assertInstanceOf(StringASTNode::class, $node);
        $this->assertEquals("test", $node->getValue());
    }

    public function testSimpleTable() {
        $parser = new LuaParser(
            new LuaTokenStream(
                new LuaInputStream(
                    '{
            foo = "bar"
        }'
                )
            )
        );

        $node = $parser->parse();

        $this->assertEquals(TableASTNode::NAME, $node->getName());
        $this->assertInstanceOf(TableASTNode::class, $node);

        $this->assertCount(1, $node->getEntries());
        $entry = $node->getEntries()[0];

        $this->assertTrue($entry->hasKey());
        $this->assertEquals(StringASTNode::NAME, $entry->getKey()->getName());
        $this->assertInstanceOf(StringASTNode::class, $entry->getKey());
        $this->assertEquals("foo", $entry->getKey()->getValue());

        $this->assertEquals(StringASTNode::NAME, $entry->getValue()->getName());
        $this->assertInstanceOf(StringASTNode::class, $entry->getValue());
        $this->assertEquals("bar", $entry->getValue()->getValue());
    }

    public function testNestedTable() {
        $parser = new LuaParser(
            new LuaTokenStream(
                new LuaInputStream(
                    '{
            foo = {
                ["test"] = {
                    1337,
                    "bar"
                }
            }
        }'
                )
            )
        );

        $node = $parser->parse();

        $this->assertEquals(TableASTNode::NAME, $node->getName());
        $this->assertInstanceOf(TableASTNode::class, $node);

        $this->assertCount(1, $node->getEntries());
        $entry = $node->getEntries()[0];

        $this->assertTrue($entry->hasKey());
        $this->assertEquals(StringASTNode::NAME, $entry->getKey()->getName());
        $this->assertInstanceOf(StringASTNode::class, $entry->getKey());
        $this->assertEquals("foo", $entry->getKey()->getValue());

        $this->assertEquals(TableASTNode::NAME, $entry->getValue()->getName());
        $this->assertInstanceOf(TableASTNode::class, $entry->getValue());
        $this->assertCount(1, $entry->getValue()->getEntries());

        $nestedEntry = $entry->getValue()->getEntries()[0];

        $this->assertTrue($nestedEntry->hasKey());
        $this->assertEquals(StringASTNode::NAME, $nestedEntry->getKey()->getName());
        $this->assertInstanceOf(StringASTNode::class, $nestedEntry->getKey());
        $this->assertEquals("test", $nestedEntry->getKey()->getValue());

        $this->assertEquals(TableASTNode::NAME, $nestedEntry->getValue()->getName());
        $this->assertInstanceOf(TableASTNode::class, $nestedEntry->getValue());
        $this->assertCount(2, $nestedEntry->getValue()->getEntries());

        $nestedNestedEntry1 = $nestedEntry->getValue()->getEntries()[0];

        $this->assertFalse($nestedNestedEntry1->hasKey());

        $this->assertEquals(NumberASTNode::NAME, $nestedNestedEntry1->getValue()->getName());
        $this->assertInstanceOf(NumberASTNode::class, $nestedNestedEntry1->getValue());
        $this->assertEquals(1337, $nestedNestedEntry1->getValue()->getValue());

        $nestedNestedEntry2 = $nestedEntry->getValue()->getEntries()[1];

        $this->assertFalse($nestedNestedEntry2->hasKey());

        $this->assertEquals(StringASTNode::NAME, $nestedNestedEntry2->getValue()->getName());
        $this->assertInstanceOf(StringASTNode::class, $nestedNestedEntry2->getValue());
        $this->assertEquals("bar", $nestedNestedEntry2->getValue()->getValue());
    }

    /**
     * @expectedException \Vlaswinkel\Lua\LuaParseException
     */
    public function testInvalid() {
        $parser = new LuaParser(new LuaTokenStream(new LuaInputStream('{ test[bar }')));

        $parser->parse();
    }
}