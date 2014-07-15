#!/usr/bin/php -q
<?php

require_once 'simpletest/autorun.php';
SimpleTest :: prefer(new TextReporter());
set_include_path('../php' . PATH_SEPARATOR . get_include_path());
require_once 'File/Set/DateSequence.php';
require_once 'File/Find/Rule.php';

ini_set('memory_limit', '2G');

//error_reporting( E_STRICT );
error_reporting( E_ALL );

class FileSetDateSequenceTest extends UnitTestCase
{
    public function __construct()
    {
    }

    public function test_new()
    {
        $this->empty_set = new File_Set_DateSequence();
        $this->assertIsA( $this->empty_set, 'File_Set_DateSequence' );
        $this->assertEqual( $this->empty_set->members(), array() );
        $this->assertEqual( $this->empty_set->size(), 0 );

        // $members = ...
        $this->directory = getcwd() . '/set';
        $f = new File_Find_Rule();
        $members = $f->name( '*.xml' )->in( $this->directory );
        $this->set = new File_Set_DateSequence(array(
            'suffix' => '.xml',    
            'directory' => $this->directory,
        ));
        $this->assertIsA( $this->set, 'File_Set_DateSequence' );
        $this->assertEqual($this->set->members(), $members);
        $this->assertEqual( $this->set->size(), count($members) );
    }

    public function test_add()
    {
        $this->old_size = $this->set->size();
        $this->file_name = $this->set->add();
        $this->new_size = $this->set->size();
        $this->assertEqual($this->new_size, $this->old_size + 1);

        // All members of a set must be unique, so can't add a file_name
        // that already exists:
        $this->expectException();
        $this->set->add( $this->file_name );
    }

    public function test_delete()
    {
        $this->set->delete( $this->file_name );
        $this->new_size = $this->set->size();
        $this->assertEqual($this->new_size, $this->old_size);

        // Doesn't make sense to delete the same file more than once:
        $this->expectException();
        $this->set->delete( $this->file_name );
    }

    public function test_add_basename()
    {
        $basename = '2009-08-30-12-44-37.xml';
        $file_name = $this->set->add( $basename );
        $this->assertEqual( basename($file_name), $basename );
        $this->assertEqual( dirname($file_name), $this->directory );
        $this->set->delete( $file_name );

        $this->expectException();
        $file_name = $this->set->add( 'foo' );
    }

    public function test_bad_dirname()
    {
        $this->expectException();
        $file_name = $this->set->add( '/foo/2009-08-30-12-44-37.xml');
    }

} // end class FileSetDateSequenceTest
