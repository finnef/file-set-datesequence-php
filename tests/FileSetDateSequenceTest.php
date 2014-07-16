<?php

namespace UmnLib\Core\Tests;

use UmnLib\Core\File\Set\DateSequence;
use Symfony\Component\Finder\Finder;

class FileSetDateSequenceTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
    }

    public function testNew()
    {
        $emptySet = new DateSequence();
        $this->assertInstanceOf('UmnLib\\Core\\File\\Set\\DateSequence', $emptySet);
        $this->assertEquals(array(), $emptySet->members());
        $this->assertEquals(0, $emptySet->size());

        // $members = ...
        $fixturesDir = dirname(__FILE__) . '/fixtures';
        $set = new DateSequence(array(
            'suffix' => '.xml',    
            'directory' => $fixturesDir,
        ));
        $this->assertInstanceOf('UmnLib\\Core\\File\\Set\\DateSequence', $set);

        $finder = new Finder();
        $members = $finder->name('*.xml')->in($fixturesDir);
        $membersArray = array();
        foreach($members as $member) {
          $membersArray[] = $member->getFilename();
        }
        $this->assertEquals($membersArray, $set->members());
        $this->assertEquals(count($membersArray), $set->size());
        return array($fixturesDir, $set);
    }

    /**
     * @depends testNew
     */
    public function testAdd(Array $testNewArgs)
    {
        list($fixturesDir, $set) = $testNewArgs;

        $oldSize = $set->size();
        $filename = $set->add();
        $newSize = $set->size();
        $this->assertEquals($newSize, $oldSize + 1);
        return $filename;
    }

    /**
     * @depends testNew
     * @depends testAdd
     * @expectedException InvalidArgumentException
     */
    public function testAddException(Array $testNewArgs, $filename)
    {
        list($fixturesDir, $set) = $testNewArgs;

        // All members of a set must be unique, so can't add a file_name
        // that already exists:
        $set->add($filename);

    }

    /**
     * @depends testNew
     * @depends testAdd
     * @expectedException InvalidArgumentException
     */
    public function testDelete(Array $testNewArgs, $filename)
    {
        list($fixturesDir, $set) = $testNewArgs;

        $oldSize = $set->size();
        $set->delete($filename);
        $this->assertEquals($oldSize - 1, $set->size());

        // Doesn't make sense to delete the same file more than once:
        $set->delete($filename);
    }

    /**
     * @depends testNew
     * @expectedException InvalidArgumentException
     */
    public function testAddBasename(Array $testNewArgs)
    {
        list($fixturesDir, $set) = $testNewArgs;

        $basename = '2009-08-30-12-44-37.xml';
        $filename = $set->add($basename);
        $this->assertEquals($basename, basename($filename));
        $this->assertEquals($fixturesDir, dirname($filename));
        $set->delete($filename);

        // Invalid argument because this file does not match the date format:
        $filename = $set->add('foo');
    }

    /**
     * @depends testNew
     * @expectedException InvalidArgumentException
     */
    public function testBadDirname(Array $testNewArgs)
    {
        list($fixturesDir, $set) = $testNewArgs;

        $filename = $set->add('/foo/2009-08-30-12-44-37.xml');
    }
}
