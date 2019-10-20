<?php

namespace ScholarsLab\BagIt\Test;

use ScholarsLab\BagIt\BagItUtils;
use ScholarsLab\BagIt\Tests\BagItTestCase;

/**
 * Test BagItUtil functions.
 *
 * @package ScholarsLab\BagIt\Test
 * @coversDefaultClass \ScholarsLab\BagIt\BagItUtils
 */
class BagItUtilsTest extends BagItTestCase
{

    /**
     * Test filterArrayMatch
     * @group BagItUtils
     * @covers ::filterArrayMatches
     */
    public function testFilterArrayMatches()
    {
        $input = array(
            'abcde',
            'bcdef',
            'cdefg',
            'defgh',
            'efghi',
            'fghij'
        );

        $this->assertEquals(1, count(BagItUtils::filterArrayMatches('/a/', $input)));

        $e = BagItUtils::filterArrayMatches('/.*e.*/', $input);
        $this->assertEquals(5, count($e));
        $this->assertEquals('abcde', $e[0][0]);
        $this->assertEquals('bcdef', $e[1][0]);
        $this->assertEquals('cdefg', $e[2][0]);
        $this->assertEquals('defgh', $e[3][0]);
        $this->assertEquals('efghi', $e[4][0]);
    }

    /**
     * Test filterArrayMatch failure.
     * @group BagItUtils
     * @covers ::filterArrayMatches
     */
    public function testFilterArrayMatchesFail()
    {
        $input = array(
            'abcde',
            'bcdef',
            'cdefg',
            'defgh',
            'efghi',
            'fghij'
        );

        $this->assertEquals(0, count(BagItUtils::filterArrayMatches('/z/', $input)));
    }

    /**
     * Test endsWith
     * @group BagItUtils
     * @covers ::endsWith
     */
    public function testEndsWithTrue()
    {
        $this->assertTrue(BagItUtils::endsWith("Scholars' Lab", 'b'));
        $this->assertTrue(BagItUtils::endsWith("Scholars' Lab", 'ab'));
        $this->assertTrue(BagItUtils::endsWith("Scholars' Lab", 'Lab'));
    }

    /**
     * @group BagItUtils
     * @covers ::endsWith
     */
    public function testEndsWithFalse()
    {
        $this->assertFalse(BagItUtils::endsWith("Scholars' Lab", 'z'));
    }

    /**
     * Utility for rls testing
     */
    private function rlsTestUtil($dirnames)
    {
        $files = array();
        foreach ($dirnames as $dirname) {
            foreach (scandir($dirname) as $filename) {
                if ($filename[0] != '.' && is_file("$dirname/$filename")) {
                    array_push($files, "$dirname/$filename");
                }
            }
        }
        sort($files);

        $lsout = BagItUtils::rls($dirnames[0]);
        sort($lsout);

        $this->assertEquals(count($files), count($lsout));

        for ($i=0; $i<count($files); $i++) {
            $this->assertEquals($files[$i], $lsout[$i]);
        }
    }

    /**
     * @group BagItUtils
     * @covers ::endsWith
     */
    public function testRlsShallow()
    {
        $dirname = __DIR__ . '/../lib';
        $this->rlsTestUtil(array($dirname));
    }

    /**
     * @group BagItUtils
     * @covers ::endsWith
     */
    public function testRlsDeep()
    {
        $dirname = __DIR__;
        $this->rlsTestUtil(
            array($dirname, "$dirname/TestBag", "$dirname/TestBag/data",
                  "$dirname/TestBag/data/imgs")
        );
    }

    /**
     * @group BagItUtils
     * @covers ::rrmdir
     */
    public function testRrmdirShallow()
    {
        $tmpdir = BagItUtils::tmpdir();

        mkdir($tmpdir);
        touch("$tmpdir/a");
        touch("$tmpdir/b");
        touch("$tmpdir/c");

        $this->assertFileExists("$tmpdir/a");

        BagItUtils::rrmdir($tmpdir);

        $this->assertFalse(file_exists($tmpdir));
        $this->assertFalse(file_exists("$tmpdir/a"));
        $this->assertFalse(file_exists("$tmpdir/b"));
        $this->assertFalse(file_exists("$tmpdir/c"));
    }

    /**
     * @group BagItUtils
     * @covers ::rrmdir
     */
    public function testRrmdirDeep()
    {
        $tmpdir = BagItUtils::tmpdir();

        mkdir($tmpdir);
        mkdir("$tmpdir/sub");
        touch("$tmpdir/sub/a");
        touch("$tmpdir/sub/b");
        touch("$tmpdir/sub/c");

        $this->assertFileExists("$tmpdir/sub/c");

        BagItUtils::rrmdir($tmpdir);

        $this->assertFalse(file_exists($tmpdir));
        $this->assertFalse(file_exists("$tmpdir/sub"));
        $this->assertFalse(file_exists("$tmpdir/sub/a"));
        $this->assertFalse(file_exists("$tmpdir/sub/b"));
        $this->assertFalse(file_exists("$tmpdir/sub/c"));
    }

    /**
     * @group BagItUtils
     * @covers ::rrmdir
     */
    public function testRrmdirFile()
    {
        $tmpdir = BagItUtils::tmpdir();
        touch($tmpdir);

        $this->assertFileExists($tmpdir);
        BagItUtils::rrmdir($tmpdir);
        $this->assertFileExists($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::tmpdir
     */
    public function testTmpdir()
    {
        $tmpdir = BagItUtils::tmpdir();
        $this->assertFalse(file_exists($tmpdir));
        $this->assertTrue(strpos($tmpdir, sys_get_temp_dir()) !== false);
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::tmpdir
     */
    public function testTmpdirPrefix()
    {
        $tmpdir = BagItUtils::tmpdir('test_');
        $this->assertStringStartsWith('test_', basename($tmpdir));
    }

    /**
     * @group BagItUtils
     * @covers ::seenAtKey
     */
    public function testSeenAtKeyIntegerKey()
    {
        $data = array(
            array('a', 'b', 'c'),
            array('d', 'e', 'f'),
            array('g', 'h', 'i')
        );

        $this->assertTrue(BagItUtils::seenAtKey($data, 0, 'a'));
        $this->assertTrue(BagItUtils::seenAtKey($data, 1, 'e'));
        $this->assertTrue(BagItUtils::seenAtKey($data, 2, 'i'));
    }

    /**
     * @group BagItUtils
     * @covers ::seenAtKey
     */
    public function testSeenAtKeyStringKey()
    {
        $data = array(
            array('a' => 1, 'z' => 2),
            array('a' => 3, 'z' => 4),
            array('a' => 5, 'z' => 6),
            array('a' => 7, 'z' => 8)
        );

        $this->assertTrue(BagItUtils::seenAtKey($data, 'a', 1));
        $this->assertTrue(BagItUtils::seenAtKey($data, 'z', 4));
        $this->assertTrue(BagItUtils::seenAtKey($data, 'a', 5));
        $this->assertTrue(BagItUtils::seenAtKey($data, 'z', 8));
    }

    /**
     * @group BagItUtils
     * @covers ::seenAtKey
     */
    public function testSeenAtKeyFail()
    {
        $data = array(
            array('a' => 1, 'z' => 2),
            array('a' => 3, 'z' => 4),
            array('a' => 5, 'z' => 6),
            array('a' => 7, 'z' => 8)
        );

        $this->assertFalse(BagItUtils::seenAtKey($data, 'a', 2));
        $this->assertFalse(BagItUtils::seenAtKey($data, 'z', 5));
        $this->assertFalse(BagItUtils::seenAtKey($data, 'a', 6));
        $this->assertFalse(BagItUtils::seenAtKey($data, 'z', 9));
        $this->assertFalse(BagItUtils::seenAtKey($data, 'm', 13));
    }

    /**
     * @group BagItUtils
     * @covers ::saveUrl
     */
    public function testSaveUrl()
    {
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);

        BagItUtils::saveUrl('http://www.google.com', "$tmpdir/google.html");

        $this->assertFileExists("$tmpdir/google.html");
        $this->assertContains(
            'html',
            strtolower(file_get_contents("$tmpdir/google.html"))
        );
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::findFirstExisting
     */
    public function testFindFirstExistingPass()
    {
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertEquals(
            "$tmpdir/c",
            BagItUtils::findFirstExisting(array("$tmpdir/a", "$tmpdir/b", "$tmpdir/c"))
        );
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::findFirstExisting
     */
    public function testFindFirstExistingFail()
    {
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertNull(
            BagItUtils::findFirstExisting(array("$tmpdir/a", "$tmpdir/b", "$tmpdir/d"))
        );
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::findFirstExisting
     */
    public function testFindFirstExistingDefault()
    {
        $tmpdir = BagItUtils::tmpdir();
        mkdir($tmpdir);

        touch("$tmpdir/c");

        $this->assertEquals(
            "$tmpdir/default",
            BagItUtils::findFirstExisting(
                array("$tmpdir/a", "$tmpdir/b", "$tmpdir/d"),
                "$tmpdir/default"
            )
        );
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::readFileText
     */
    public function testReadFileText()
    {
        $tmpdir = $this->prepareTestBagDirectory();
        $this->assertEquals(
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n",
            BagItUtils::readFileText("{$tmpdir}/bagit.txt", 'UTF-8')
        );
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::readLines
     */
    public function testReadLines()
    {
        $tmpdir = $this->prepareTestBagDirectory();
        $lines = BagItUtils::readLines("{$tmpdir}/bagit.txt", 'UTF-8');
        $this->assertEquals(2, count($lines));
        $this->assertEquals("BagIt-Version: 0.96", $lines[0]);
        $this->assertEquals("Tag-File-Character-Encoding: UTF-8", $lines[1]);
        BagItUtils::rrmdir($tmpdir);
    }

    /**
     * @group BagItUtils
     * @covers ::writeFileText
     */
    public function testWriteFileText()
    {
        $tmpfile = BagItUtils::tmpdir();

        BagItUtils::writeFileText(
            $tmpfile,
            'UTF-8',
            "This is some text.\nYep, it sure is.\n"
        );

        $this->assertEquals(
            "This is some text.\nYep, it sure is.\n",
            file_get_contents($tmpfile)
        );
        BagItUtils::rrmdir($tmpfile);
    }

    /**
     * @group BagItUtils
     * @covers ::sanitizeFileName
     */
    public function testSanitizeFileNameWhiteSpace()
    {
        $this->assertEquals(
            "this_contained_significant_whitespace_at_one_time",
            BagItUtils::sanitizeFileName("this contained\tsignificant\t" .
                                   "whitespace   at      one        time")
        );
    }

    /**
     * @group BagItUtils
     * @covers ::sanitizeFileName
     */
    public function testSanitizeFileNameRemove()
    {
        $this->assertEquals(
            'thisthatwow',
            BagItUtils::sanitizeFileName("this&that###wow!!!!~~~???")
        );
    }

    /**
     * @group BagItUtils
     * @covers ::sanitizeFileName
     */
    public function testSanitizeFileNameDevs()
    {
        $this->assertStringStartsWith('nul_', BagItUtils::sanitizeFileName('NUL'));
        $this->assertStringStartsWith('aux_', BagItUtils::sanitizeFileName('AUX'));
        $this->assertStringStartsWith('com3_', BagItUtils::sanitizeFileName('COM3'));
        $this->assertStringStartsWith('lpt6_', BagItUtils::sanitizeFileName('LPT6'));
    }

    /**
     * @group BagItUtils
     * @covers ::sanitizeFileName
     */
    public function testSanitizeFileName()
    {
        $this->assertEquals(
            'this-is-ok.txt',
            BagItUtils::sanitizeFileName('this-is-ok.txt')
        );
    }

    /**
     * @group BagItUtils
     * @covers ::readBagItFile
     */
    public function testReadBagItFile()
    {
        $tmpdir = $this->prepareTestBagDirectory();
        $filename = "{$tmpdir}/bagit.txt";
        list($versions, $encoding, $errors) = BagItUtils::readBagItFile($filename);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
        $this->assertEquals('UTF-8', $encoding);
        $this->assertEquals(0, count($errors));
    }

    /**
     * @group BagItUtils
     * @covers ::readBagItFile
     */
    public function testReadBagItFileNoVersion()
    {
        $tmpfile = BagItUtils::tmpdir('bagit_');
        file_put_contents(
            $tmpfile,
            "Tag-File-Character-Encoding: ISO-8859-1\n"
        );

        list($versions, $encoding, $errors) = BagItUtils::readBagItFile($tmpfile);
        $this->assertNull($versions);
        $this->assertEquals('ISO-8859-1', $encoding);
        $this->assertEquals(1, count($errors));
        $this->assertEquals('bagit', $errors[0][0]);
        $this->assertEquals(
            'Error reading version information from bagit.txt file.',
            $errors[0][1]
        );
        unlink($tmpfile);
    }

    /**
     * @group BagItUtils
     * @covers ::readBagItFile
     */
    public function testReadBagItFileNoEncoding()
    {
        $tmpfile = BagItUtils::tmpdir('bagit_');
        file_put_contents(
            $tmpfile,
            "BagIt-Version: 0.96\n"
        );

        list($versions, $encoding, $errors) = BagItUtils::readBagItFile($tmpfile);
        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);

        // I'm not entirely sure that this is the behavior I want here.
        // I think maybe it should set the default (UTF-8) and signal an
        // error.
        $this->assertNull($encoding);
        $this->assertEquals(0, count($errors));
        unlink($tmpfile);
    }

    /**
     * @group BagItUtils
     * @covers ::readBagItFile
     */
    public function testReadBagItFileMissing()
    {
        $filename = __DIR__ . '/doesn-not-exist';
        list($versions, $encoding, $errors) = BagItUtils::readBagItFile($filename);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
        $this->assertEquals('UTF-8', $encoding);
        $this->assertEquals(0, count($errors));
    }

    /**
     * @group BagItUtils
     * @covers ::parseVersionString
     */
    public function testParseVersionStringPass()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $versions = BagItUtils::parseVersionString($data);

        $this->assertEquals(2, count($versions));
        $this->assertEquals(0, $versions['major']);
        $this->assertEquals(96, $versions['minor']);
    }

    /**
     * @group BagItUtils
     * @covers ::parseVersionString
     */
    public function testParseVersionStringFail()
    {
        $data =
            "BagIt-Versions: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $versions = BagItUtils::parseVersionString($data);

        $this->assertNull($versions);
    }

    /**
     * @group BagItUtils
     * @covers ::parseEncodingString
     */
    public function testParseEncodingStringPass()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-Encoding: UTF-8\n";
        $encoding = BagItUtils::parseEncodingString($data);
        $this->assertEquals('UTF-8', $encoding);
    }

    /**
     * @group BagItUtils
     * @covers ::parseEncodingString
     */
    public function testParseEncodingStringFail()
    {
        $data =
            "BagIt-Version: 0.96\n" .
            "Tag-File-Character-encoding: UTF-8\n";
        $encoding = BagItUtils::parseEncodingString($data);
        $this->assertNull($encoding);
    }

    /**
     * Utility
     */
    private function clearTagManifest($directory)
    {
        // Other tests add a tagmanifest-sha1.txt, which isn't in the
        // archives, at the end of the list. Rm it.
        $rmfile = "{$directory}/tagmanifest-sha1.txt";
        if (file_exists($rmfile)) {
            unlink($rmfile);
        }
    }

    /**
     * @group BagItUtils
     * @covers ::uncompressBag
     */
    public function testUncompressBagZip()
    {
        $output = BagItUtils::uncompressBag(self::TEST_BAG_ZIP);
        $fullBag = $this->prepareTestBagDirectory();

        $this->assertFileExists($output);
        $this->assertTrue(strpos($output, sys_get_temp_dir()) !== false);

        $this->clearTagManifest($fullBag);

        $bagFiles = BagItUtils::rls($fullBag);
        sort($bagFiles);
        $outFiles = BagItUtils::rls($output);
        sort($outFiles);

        $this->assertEquals(count($bagFiles), count($outFiles));
        for ($i=0; $i<count($outFiles); $i++) {
            $this->assertEquals(
                basename($bagFiles[$i]),
                basename($outFiles[$i])
            );
        }
        BagItUtils::rrmdir($output);
        BagItUtils::rrmdir($fullBag);
    }

    /**
     * @group BagItUtils
     * @covers ::uncompressBag
     */
    public function testUncompressBagTar()
    {
        $output = BagItUtils::uncompressBag(self::TEST_BAG_TGZ);
        $fullBag = $this->prepareTestBagDirectory();

        $this->assertFileExists($output);
        $this->assertTrue(strpos($output, sys_get_temp_dir()) !== false);

        $this->clearTagManifest($fullBag);

        $bagFiles = BagItUtils::rls($fullBag);
        sort($bagFiles);
        $outFiles = BagItUtils::rls($output);
        sort($outFiles);

        $this->assertEquals(count($bagFiles), count($outFiles));
        for ($i=0; $i<count($outFiles); $i++) {
            $this->assertEquals(
                basename($bagFiles[$i]),
                basename($outFiles[$i])
            );
        }
        BagItUtils::rrmdir($output);
        BagItUtils::rrmdir($fullBag);
    }

    /**
     * @expectedException \ErrorException
     * @group BagItUtils
     * @covers ::uncompressBag
     */
    public function testUncompressBagError()
    {
        BagItUtils::uncompressBag(__DIR__);
    }

    /* TODO: Fix these so that they're testing correctly.
    public function testBagIt_compressBagZip()
    {
        $this->_clearTagManifest();

        $output = tmpdir() . '.zip';
        BagIt_compressBag(__DIR__ . '/TestBag', $output, 'zip');

        $this->assertFileEquals(__DIR__ . '/TestBag.zip', $output);
    }

    public function testBagIt_compressBagTar()
    {
        $this->_clearTagManifest();

        $output = tmpdir() . '.tgz';
        BagIt_compressBag(__DIR__ . '/TestBag', $output, 'tgz');

        $this->assertFileEquals(__DIR__ . '/TestBag.tgz', $output);
    }
     */

    /**
     * @group BagItUtils
     * @covers ::validateExists
     */
    public function testValidateExistsPass()
    {
        $errors = array();
        $this->assertTrue(BagItUtils::validateExists(__FILE__, $errors));
        $this->assertEquals(0, count($errors));
    }

    /**
     * @group BagItUtils
     * @covers ::validateExists
     */
    public function testValidateExistsFail()
    {
        $errors = array();
        $this->assertFalse(
            BagItUtils::validateExists(__DIR__ . '/not-here', $errors)
        );
        $this->assertEquals(1, count($errors));
        $this->assertEquals('not-here', $errors[0][0]);
        $this->assertEquals('not-here does not exist.', $errors[0][1]);
    }

    /**
     * @group BagItUtils
     * @covers ::parseBagInfo
     */
    public function testParseBaseInfoEmptyLine()
    {
        $lines = array(
            'some: here',
            '',
            'other: there'
        );

        $info = BagItUtils::parseBagInfo($lines);
        $this->assertEquals('here', $info['some']);
        $this->assertEquals('there', $info['other']);
    }

    /**
     * @group BagItUtils
     * @covers ::parseBagInfo
     */
    public function testParseBaseInfoContinued()
    {
        $lines = array(
            'some: here',
            ' and there',
            'other: there',
            "\tand here"
        );

        $info = BagItUtils::parseBagInfo($lines);
        $this->assertEquals('here and there', $info['some']);
        $this->assertEquals('there and here', $info['other']);
    }

    /**
     * @group BagItUtils
     * @covers ::parseBagInfo
     */
    public function testParseBaseInfoStandard()
    {
        $lines = array(
            'some: here',
            'other: there'
        );

        $info = BagItUtils::parseBagInfo($lines);
        $this->assertEquals('here', $info['some']);
        $this->assertEquals('there', $info['other']);
    }
}
