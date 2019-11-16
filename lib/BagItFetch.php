<?php
/**
 * This is a PHP implementation of the {@link
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really,
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for
 * PHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of * the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

namespace ScholarsLab\BagIt;

/**
 * This is a utility class for managing fetch files.
 *
 * These files map file names to hashes.
 *
 * @category  FileUtils
 * @package   ScholarsLab\BagIt
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @author    Jared Whiklo <jwhiklo@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   Release: 1.0.0
 * @link      https://github.com/ScholarsLab/BagItPHP
 */
class BagItFetch
{

    //{{{ Properties

    /**
     * The file name containing the fetch information.
     *
     * @var string
     */
    private $fileName;

    /**
     * The data from the fetch file.
     *
     * This is an array-list containing array-mappings with the keys 'url',
     * 'length', and 'filename'.
     *
     * @var array
     */
    private $data;

    /**
     * The character encoding for the data in the fetch file.
     *
     * @var string
     */
    private $fileEncoding;

    //}}}

    //{{{ Public methods

    /**
     * This initializes a new BagItFetch instance.
     *
     * @param string $fileName
     *   This is the file name for the fetch file.
     * @param string $fileEncoding
     *   This is the encoding to use when reading or writing the fetch file. The default is 'UTF-8'.
     */
    public function __construct($fileName, $fileEncoding = 'UTF-8')
    {
        $this->fileName = $fileName;
        $this->fileEncoding = $fileEncoding;
        $this->data = array();

        if (file_exists($this->fileName)) {
            $this->read();
        }
    }

    /**
     * This returns the fetch data.
     *
     * @return array The fetch data.
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * This reads the data from the fetch file and populates the data array.
     */
    public function read()
    {
        $lines = BagItUtils::readLines($this->fileName, $this->fileEncoding);
        $fetch = array();

        foreach ($lines as $line) {
            $fields = preg_split('/\s+/', $line);
            if (count($fields) == 3) {
                array_push(
                    $fetch,
                    array('url' => $fields[0],
                          'length' => $fields[1],
                          'filename' => $fields[2])
                );
            }
        }
        $this->data = $fetch;
    }

    /**
     * This writes the data to the fetch file.
     *
     * @return void
     */
    public function write()
    {
        $lines = array();

        foreach ($this->data as $fetch) {
            $data = array($fetch['url'], $fetch['length'], $fetch['filename']);
            array_push($lines, join(' ', $data) . "\n");
        }

        if (count($lines) == 0) {
            if (file_exists($this->fileName)) {
                unlink($this->fileName);
            }
        } else {
            BagItUtils::writeFileText($this->fileName, $this->fileEncoding, join('', $lines));
        }
    }

    /**
     * This clears the fetch data and the file.
     *
     * @return void
     */
    public function clear()
    {
        $this->data = array();
        file_put_contents($this->fileName, '');
    }

    /**
     * This adds an entry to the fetch data.
     *
     * @param string $url      This is the URL to load the file from.
     * @param string $filename This is the file name, relative to the fetch
     * file's directory, to save the data to.
     *
     * @return void
     */
    public function add($url, $filename)
    {
        array_push(
            $this->data,
            array('url' => $url, 'length' => '-', 'filename' => $filename)
        );
        $this->write();
    }

    /**
     * This downloads the files in the fetch information that aren't on the
     * file system.
     *
     * @return void
     */
    public function download()
    {
        $basedir = dirname($this->fileName);
        foreach ($this->data as $fetch) {
            $filename = $basedir . '/' . $fetch['filename'];
            if (! file_exists($filename)) {
                $this->downloadFile($fetch['url'], $filename);
            }
        }
    }

    /**
     * Get the filename.
     *
     * @return string this filename.
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Get the fetch file's encoding.
     *
     * @return string the file encoding.
     */
    public function getFileEncoding()
    {
        return $this->fileEncoding;
    }

    /**
     * Overwrite the datastore with an array of arrays with keys 'url',
     * 'length' and 'filename'. This DOES NOT flush to disk, you must call
     * write() explicitly.
     *
     * @param array $data the data to load.
     */
    public function load(array $data)
    {
        $this->data = array();
        foreach ($data as $datum) {
            if (is_array($datum) &&
                array_key_exists('url', $datum) &&
                array_key_exists('length', $datum) &&
                array_key_exists('filename', $datum)) {
                $this->data[] = $datum;
            }
        }
    }

    //}}}

    //{{{ Private methods

    /**
     * This downloads a single file.
     *
     * @param string $url      The URL to fetch.
     * @param string $filename The absolute file name to save to.
     *
     * @return void
     */
    private function downloadFile($url, $filename)
    {
        $dirname = dirname($filename);
        if (! is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        BagItUtils::saveUrl($url, $filename);
    }

    //}}}
}
