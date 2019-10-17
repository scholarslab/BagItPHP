<?php

namespace ScholarsLab\BagIt\Test;

use PHPUnit\Framework\TestCase;
use ScholarsLab\BagIt\BagIt;
use ScholarsLab\BagIt\BagItFetch;
use ScholarsLab\BagIt\BagItUtils;

/**
 * Class BagItFetchTest
 * @package ScholarsLab\BagIt\Test
 * @coversDefaultClass \ScholarsLab\BagIt\BagItFetch
 */
class BagItFetchTest extends TestCase
{
    /**
     * @var string
     */
    private $tmpdir;

    /**
     * @var \ScholarsLab\BagIt\BagItFetch
     */
    private $fetch;

    public function setUp()
    {
        $this->tmpdir = BagItUtils::tmpdir();
        mkdir($this->tmpdir);

        file_put_contents(
            "{$this->tmpdir}/fetch.txt",
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n"
        );

        $this->fetch = new BagItFetch("{$this->tmpdir}/fetch.txt");
    }

    public function tearDown()
    {
        BagItUtils::rrmdir($this->tmpdir);
    }

    /**
     * Test filename setting.
     * @group BagItFetch
     * @covers ::getFileName
     */
    public function testFileName()
    {
        $this->assertEquals(
            "{$this->tmpdir}/fetch.txt",
            $this->fetch->getFileName()
        );
    }

    /**
     * Test data structure.
     * @group BagItFetch
     * @covers ::getData
     */
    public function testData()
    {
        $data = $this->fetch->getData();

        $this->assertEquals(2, count($data));
        $this->assertEquals("http://www.google.com", $data[0]['url']);
        $this->assertEquals("http://www.yahoo.com", $data[1]['url']);
    }

    /**
     * Test read from file on disk.
     * @group BagItFetch
     * @covers ::read
     */
    public function testRead()
    {
        file_put_contents(
            "{$this->tmpdir}/fetch.txt",
            "http://www.scholarslab.org/ - data/scholarslab/index.html"
        );

        $this->fetch->read();

        $this->assertFalse(
            array_key_exists('data/google/index.html', $this->fetch->getData())
        );
        $this->assertFalse(
            array_key_exists('data/yahoo/index.html', $this->fetch->getData())
        );
        $this->assertEquals(
            'data/scholarslab/index.html',
            $this->fetch->getData()[0]['filename']
        );
    }

    /**
     * Test write to file on disk.
     * @group BagItFetch
     * @covers ::write
     * @covers ::load
     */
    public function testWrite()
    {
        $data = $this->fetch->getData();
        array_push(
            $data,
            array('url' => 'http://www.scholarslab.org/', 'length' => '-', 'filename' => 'data/scholarslab/index.html')
        );
        $this->fetch->load($data);

        $this->fetch->write();
        $this->assertFileExists("{$this->tmpdir}/fetch.txt");
        $this->assertEquals(
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n" .
            "http://www.scholarslab.org/ - data/scholarslab/index.html\n",
            file_get_contents("{$this->tmpdir}/fetch.txt")
        );
    }

    /**
     * Test get data.
     * @group BagItFetch
     * @covers ::getData
     */
    public function testGetData()
    {
        $data = $this->fetch->getData();

        $this->assertEquals(2, count($data));
        $this->assertEquals("http://www.google.com", $data[0]['url']);
        $this->assertEquals("http://www.yahoo.com", $data[1]['url']);
    }

    /**
     * Test download data.
     * @group BagItFetch
     * @covers ::download
     */
    public function testDownload()
    {
        $tmp = $this->tmpdir;

        $this->assertFileNotExists("$tmp/data/google/index.html");
        $this->assertFileNotExists("$tmp/data/yahoo/index.html");

        $this->fetch->download();

        $this->assertFileExists("$tmp/data/google/index.html");
        $this->assertFileExists("$tmp/data/yahoo/index.html");
    }

    /**
     * Test add file to fetch list.
     * @group BagItFetch
     * @covers ::add
     */
    public function testAdd()
    {
        $this->assertCount(2, $this->fetch->getData());

        $this->fetch->add(
            'http://www.scholarslab.org/',
            'data/scholarslab/index.html'
        );

        $this->assertCount(3, $this->fetch->getData());
        $this->assertEquals(
            'data/scholarslab/index.html',
            $this->fetch->getData()[2]['filename']
        );

        $this->assertEquals(
            "http://www.google.com - data/google/index.html\n" .
            "http://www.yahoo.com - data/yahoo/index.html\n" .
            "http://www.scholarslab.org/ - data/scholarslab/index.html\n",
            file_get_contents("{$this->tmpdir}/fetch.txt")
        );
    }

    /**
     * Test clearing fetch data.
     * @group BagItFetch
     * @covers ::clear
     */
    public function testClear()
    {
        $this->assertCount(2, $this->fetch->getData());

        $this->fetch->clear();

        $this->assertCount(0, $this->fetch->getData());
        $this->assertFalse(
            array_key_exists('data/google/index.html', $this->fetch->getData())
        );
        $this->assertFalse(
            array_key_exists('data/yahoo/index.html', $this->fetch->getData())
        );
    }

    /**
     * Test writing empty file.
     * @group BagItFetch
     * @covers ::clear
     * @covers ::write
     */
    public function testEmptyWrite()
    {
        $this->fetch->clear();
        $this->fetch->write();
        $this->assertFileNotExists("{$this->tmpdir}/fetch.txt");
    }

    /**
     * Test that a new bag has no fetch.txt by default.
     * @group BagItFetch
     * @covers ::update
     */
    public function testNewBagEmpty()
    {
        $bagdir = "{$this->tmpdir}/_bag";

        $bag    = new BagIt($bagdir);
        $this->assertFileNotExists("$bagdir/fetch.txt");

        $bag->update();
        $this->assertFileNotExists("$bagdir/fetch.txt");
    }
}
