<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is a PHP implementation of the {@link 
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really, 
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for 
 * PHP.
 * 
 * PHP version 5 
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by
 * applicable law or agreed to in writing, software distributed under the
 * License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
 * OF ANY KIND, either express or implied. See the License for the specific
 * language governing permissions and limitations under the License.
 *
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   0.1
 * @link      https://github.com/erochest/BagItPHP
 *
 */


require_once 'Archive/Tar.php';
require_once 'bagit_utils.php';


/**
 * This is a class for all bag exceptions.
 * 
 * @category   FileUtils
 * @package    Bagit
 * @subpackage Exception
 * @author     Eric Rochester <erochest@gmail.com>
 * @author     Wayne Graham <wayne.graham@gmail.com>
 * @copyright  2011 The Board and Visitors of the University of Virginia
 * @license    http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version    0.1
 * @link       https://github.com/erochest/BagItPHP
 */
class BagItException extends Exception 
{

}


/**
 * This is the main class for interacting with a bag.
 * 
 * @category  FileUtils
 * @package   Bagit
 * @author    Eric Rochester <erochest@gmail.com>
 * @author    Wayne Graham <wayne.graham@gmail.com>
 * @copyright 2011 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   0.1
 * @link      https://github.com/erochest/BagItPHP
 */
class BagIt 
{

    // {{{ properties
  
    /**
     * The bag as passed into the constructor. This could be a directory or a 
     * file name, and it may not exist.
     * 
     * @var string
     */
    var $bag;

    /**
     * Absolute path to the bag directory.
     * 
     * @var string
     */
    var $bagDirectory;

    /**
     * True if the bag is extended.
     * 
     * @var boolean
     */
    var $extended;

    /**
     * 'sha1' or 'md5'. Default is 'sha1'.
     * 
     * @var string
     */
    var $hashEncoding;

    /**
     * The major version number declared in 'bagit.txt'. Default is '0'.
     * 
     * @var string
     */
    var $bagMajorVersion;

    /**
     * The minor version number declared in 'bagit.txt'. Default is '96'.
     * 
     * @var string
     */
    var $bagMinorVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     * 
     * @var string
     */
    var $tagFileEncoding;

    /**
     * Absolute path to the data directory.
     * 
     * @var string
     */
    var $dataDirectory;

    /**
     * Absolute path to the bagit file.
     * 
     * @var string
     */
    var $bagitFile;

    /**
     * Absolute path to the 'manifest-{sha1,md5}.txt' file.
     * 
     * @var string
     */
    var $manifestFile;

    /**
     * Absolute path to the 'tagmanifest-{sha1,md5}.txt' file or null.
     * 
     * @var string
     */
    var $tagManifestFile;

    /**
     * Absolute path to the 'fetch.txt' file or null.
     * 
     * @var string
     */
    var $fetchFile;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     * 
     * @var string
     */
    var $bagInfoFile;

    /**
     * A dictionary array containing the manifest file contents.
     * 
     * @var array
     */
    var $manifestContents;

    /**
     * A dictionary array containing the tagmanifest file contents.
     * 
     * @var array
     */
    var $tagManifestContents;

    /**
     * A dictionary array containing the 'fetch.txt' file contents.
     * 
     * @var array
     */
    var $fetchContents;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     * 
     * @var array
     */
    var $bagInfoContents;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or 
     * 'zip' to indicate the file's compression format.
     * 
     * @var string
     */
    var $bagCompression;

    /**
     * An array of all bag validation errors. Each entries is a two-element array 
     * containing the path of the file and the error message.
     * 
     * @var array
     */
    var $bagErrors;

    //}}}

    //{{{ Public Methods

    /**
     * Define a new BagIt instance.
     *
     * @param string  $bag      Either a non-existing folder name (will create a new 
     * bag here); an existing folder name (this will treat it as a bag and 
     * create any missing files or folders needed); or an existing compressed 
     * file (this will un-compress it to a temporary directory and treat it as 
     * a bag).
     * @param boolean $validate This will validate all files in the bag, 
     * including running checksums on all of them. Default is false.
     * @param boolean $extended This will ensure that optional 'bag-info.txt', 
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is 
     * true.
     * @param boolean $fetch    If true, it will download all files in 
     * 'fetch.txt'. Default is false.
     */
    public function BagIt($bag, $validate=false, $extended=true, $fetch=false) 
    {
        $this->bag = $bag;
        $this->extended = $extended;
        $this->hashEncoding = 'sha1';
        $this->bagMajorVersion = 0;
        $this->bagMinorVersion = 96;
        $this->tagFileEncoding = 'UTF-8';
        $this->dataDirectory = null;
        $this->bagDirectory = null;
        $this->bagitFile = null;
        $this->manifestFile = null;
        $this->tagManifestFile = null;
        $this->fetchFile = null;
        $this->bagInfoFile = null;
        $this->manifestContents = null;
        $this->tagManifestContents = null;
        $this->fetchContents = null;
        $this->bagInfoContents = null;
        $this->bagCompression = null;
        $this->bagErrors = array();

        if (file_exists($this->bag)) {
            $this->openBag();
        } else {
            $this->createBag();
        }

        if ($fetch) {
            $this->fetch();
        }

        if ($validate) {
            $this->validate();
        }
    }

    /**
     * Test if a Bag is valid
     *
     * @return boolean True if no validation errors occurred.
     */
    public function isValid()
    {
        return (count($this->bagErrors) == 0);
    }

    /**
     * Test if a bag has optional files
     *
     * @return boolean True if the bag contains the optional files 
     * 'bag-info.txt', 'fetch.txt', or 'tagmanifest-{sha1,md5}.txt'.
     */
    function isExtended() 
    {
        return $this->extended;
    }

    /**
     * Return the info keys
     *
     * @return array A dictionary array containing these keys: 'version', 
     * 'encoding', 'hash'.
     */
    function getBagInfo() 
    {
        $info = array(
            'version'  => "{$this->bagMajorVersion}.{$this->bagMinorVersion}",
            'encoding' => $this->tagFileEncoding,
            'hash'     => $this->hashEncoding
        );
        return $info;
    }

    /**
     * Get the absolute path of the bag's data directory
     *
     * @return string The absolute path to the bag's data directory.
     */
    function getDataDirectory() 
    {
        return $this->dataDirectory;
    }

    /**
     * Determine hash encoding
     *
     * @return string The bag's checksum encoding scheme.
     */
    function getHashEncoding() 
    {
        return $this->hashEncoding;
    }

    /**
     * Sets the bag's checksum hash algorithm.
     *
     * @param string $hashAlgorithm The bag's checksum hash algorithm. Must be 
     * either 'sha1' or 'md5'.
     */
    function setHashEncoding($hashAlgorithm) 
    {
        $hashAlgorithm = strtolower($hashAlgorithm);
        if ($hashAlgorithm != 'md5' && $hashAlgorithm != 'sha1') {
            throw new Exception("Invalid hash algorithim: '$hashAlgorithm'.");
        }

        $this->hashEncoding = $hashAlgorithm;
        $this->manifestFile = "{$this->bagDirectory}/manifest-{$hashAlgorithm}.txt";
        if ($this->tagManifestFile != null) {
            $this->tagManifestFile = "{$this->bagDirectory}/tagManifest-{$hashAlgorithm}.txt";
        }
    }

    /**
     * Return an array of all files in the data directory
     *
     * @return array An array of absolute paths for all of the files in the data 
     * directory.
     */
    function getBagContents() 
    {
        return rls($this->dataDirectory);
    }

    /**
     * Return errors for a bag
     *
     * @param boolean $validate If true, then it will run this->validate() to 
     * verify the integrity first. Default is false.
     *
     * @return array An array of all bag errors.
     */
    function getBagErrors($validate=false) 
    {
        if ($validate) {
            $this->validate();
        }
        return $this->bagErrors;
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the
     * presence of required iles and folders and verifies the checksum for 
     * each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     *
     * @return array The list of bag errors.
     */
    function validate() 
    {
        $errors = array();

        $this->validateExists($this->bagitFile, $errors);
        $this->validateExists($this->dataDirectory, $errors);
        $this->validateExists($this->manifestFile, $errors);

        $stripLen = strlen($this->bagDirectory) + 1;
        if (is_dir($this->dataDirectory) && file_exists($this->manifestFile)) {
            foreach ($this->getBagContents() as $filename) {
                $this->validateChecksum($filename, $stripLen, $errors);
            }

        } else {
            array_push(
                $errors,
                array('checksum verification', 'Unable to verify manifest.')
            );
        }

        $this->bagErrors = $errors;
        return $this->bagErrors;
    }

    /**
     * This method is used whenever something is added to or removed from the 
     * bag. It performs these steps:
     *
     * <ul>
     * <li>Ensures that required files are present;</li>
     * <li>Sanitizes file names;</li>
     * <li>Makes sure that checksums are up-to-date;</li>
     * <li>Adds checksums and file entries for new files;</li>
     * <li>Removes checksums and file entries for missing files; and</li>
     * <li>If it's an extended bag, makes sure that those files are also
     * up-to-date.</li>
     * </ul>
     *
     * @return void
     */
    function update() 
    {
        $this->updateManifestFileNames();

        // Clean up old manifest files. We'll regenerate them later.
        $this->clearManifests();

        // Sanitize files in the data directory.
        $this->cleanDataFileNames();

        // Update data file checksums.
        $dataFiles = rls($this->dataDirectory);
        $this->manifestContents = $this->updateManifest(
            $dataFiles,
            $this->manifestFile
        );

        // Update meta-file checksums.
        $bagdir = $this->bagDirectory;
        $tagFiles = array(
            "$bagdir/bagit.txt",
            "$bagdir/bag-info.txt",
            "$bagdir/fetch.txt",
            $this->manifestFile
        );
        $this->tagManifestContents = $this->updateManifest(
            $tagFiles,
            $this->tagManifestFile
        );
    }

    /**
     * Downloads every entry in 'fetch.txt'.
     *
     * @param boolean $validate If true, then it also calls update() and 
     * validate().
     *
     * @return void
     */
    function fetch($validate=false) 
    {
        foreach ($this->fetchContents as $fetch) {
            $filename = $this->bagDirectory . '/' . $fetch['filename'];
            if (! file_exists($filename)) {
                $dirname = dirname($filename);
                if (! is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }

                try {
                    $curl = curl_init($fetch['url']);
                    $fp = fopen($filename, 'w');

                    curl_setopt($curl, CURLOPT_FILE, $fp);
                    curl_setopt($curl, CURLOPT_HEADER, 0);

                    curl_exec($curl);
                    curl_close($curl);

                    fclose($fp);
                } catch (Exception $e) {
                    array_push(
                        $this->bagErrors,
                        array('fetch', 'URL ' . $fetch['url'] . 
                          ' could down be downloaded.')
                    );
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                }
            }
        }

        if ($validate) {
            $this->update();
            $this->validate();
        }
    }

    /**
     * Writes new entries in 'fetch.txt'.
     *
     * @param array   $fetchEntries An array containing the URL and path relative to 
     * the data directory for file.
     * @param boolean $append       If false, the current entries in 'fetch.txt' 
     * will be overwritten. Default is true.
     *
     * @return void
     */
    function addFetchEntries($fetchEntries, $append=true)
    {
        $fetches = array();
        foreach ($fetchEntries as $fetch) {
            list($url, $filename) = $fetch;
            array_push(
                $fetches,
                array('url' => $url, 'length' => '-', 'filename' => $filename)
            );
        }

        if ($append) {
            $this->fetchContents = array_merge($this->fetchContents, $fetches);
        } else {
            $this->fetchContents = $fetches;
        }

        $this->writeArrayToFetch();
    }

    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method      Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @return void
     */
    function package($destination, $method='tgz')
    {
        $method = strtolower($method);
        if ($method != 'zip' && $method != 'tgz') {
            throw new BagItException("Invalid compression method: '$method'.");
        }

        if (substr_compare($destination, ".$method", -4, 4, true) != 0) {
            $destination = "$destination.$method";
        }

        $package = $this->compressBag($method);
        rename($package, $destination);
    }
    //}}}

    //{{{ Private Methods

    /**
     * This cleans up the manifest files.
     */
    private function clearManifests()
    {
        $basenames = array(
            'manifest-sha1.txt',
            'manifest-md5.txt',
            'tagmanifest-sha1.txt',
            'tagmanifest-md5.txt'
        );

        foreach ($basenames as $basename)
        {
            $fullname = "{$this->bagDirectory}/$basename";
            if (file_exists($fullname))
            {
                unlink($fullname);
            }
        }

        $this->manifestContents = array();
        $this->tagManifestContents = array();
    }

    /**
     * This is a facade method that takes a list of files, generates a checksum 
     * array, and writes it to a file before returning it.
     *
     * @param array $files      The list of absolute file names to generate 
     * checksums for.
     * @param string $filename  The name of the file to write the checksums out 
     * to.
     * @return array            An array mapping relative file names to 
     * checksum hashes.
     */
    private function updateManifest($files, $filename)
    {
        $csums = $this->makeChecksumArray($files);
        $this->writeChecksumArray($csums, $filename);
        return $csums;
    }

    /**
     * This cleans up the file names of all the files in the data/ directory.
     */
    private function cleanDataFileNames()
    {
        $dataFiles = rls($this->dataDirectory);
        foreach ($dataFiles as $dataFile) {
            $baseName = basename($dataFile);
            if ($baseName == '.' || $baseName == '..') {
                continue;
            }

            $cleanName = $this->sanitizeFileName($baseName);
            if ($cleanName === null) {
                unlink($dataFile);
            } else if ($baseName != $cleanName) {
                $dirName = dirname($dataFile);
                rename($dataFile, "$dirName/$cleanName");
            }
        }
    }

    /**
     * This validates that a file or directory exists.
     *
     * @param string $filename The file name to check for.
     * @param array $errors    The list of errors to add the message to, if the 
     * file doesn't exist.
     *
     * @return boolean True if the file does exist; false otherwise.
     */
    private function validateExists($filename, &$errors)
    {
        if (! file_exists($filename))
        {
            $basename = basename($filename);
            array_push(
                $errors,
                array($basename, "$basename does not exist.")
            );
            return false;
        }
        return true;
    }

    /**
     * This validates a file's checksum.
     *
     * @param string $filename   The complete filename to check.
     * @param integer $prefixLen The length of the prefix to strip off to get 
     * the name of the file relative to the bag directory.
     * @param errors             The list of errors to add messages to, if the 
     * file doesn't validate.
     */
    private function validateChecksum($filename, $prefixLen, &$errors)
    {
        $relname = substr($filename, $prefixLen);
        $expected = $this->manifestContents[$relname];
        $actual = $this->calculateChecksum($filename);

        if ($expected === null) {
            array_push(
                $errors,
                array($relname, 'File missing from manifest.')
            );
        } else if ($expected != $actual) {
            array_push($errors, array($relname, 'Checksum mismatch.'));
        }
    }

    /**
     * Read the data in the file and convert it from tagFileEncoding into 
     * UTF-8.
     *
     * @param string $filename The name of the file to read.
     *
     * @return string The contents of the file as UTF-8.
     */
    private function readFile($filename) 
    {
        $data = iconv(
            $this->tagFileEncoding,
            'UTF-8',
            file_get_contents($filename)
        );
        return $data;
    }

    /**
     * Write the data in the file, converting it from UTF-8 to tagFileEncoding.
     *
     * @param string $filename The name of the file to write to.
     * @param string $data     The data to write.
     *
     * @return void
     */
    private function writeFile($filename, $data) 
    {
        file_put_contents(
            $filename,
            iconv('UTF-8', $this->tagFileEncoding, $data)
        );
    }

    /**
     * Read the data in the file and convert it from tagFileEncoding into 
     * UTF-8, then split the lines.
     *
     * @param string $filename The name of the file to read.
     *
     * @return array The lines in the file.
     */
    private function readLines($filename) 
    {
        $data = $this->readFile($filename);
        $lines = preg_split('/[\n\r]+/', $data, null, PREG_SPLIT_NO_EMPTY);
        return $lines;
    }

    /**
     * Open an existing bag. This expects $bag to be set.
     *
     * @return void
     */
    private function openBag() 
    {
        if ($this->isCompressed()) {
            $matches = array();
            $success = preg_match(
                '/^(.*)\.(zip|tar\.gz|tgz)/',
                basename($this->bag),
                $matches
            );
            if ($success) {
                $base = $matches[1];
                $this->bagDirectory = $this->uncompressBag($base);
            } else {
                throw new BagItException(
                    "Invalid compressed bag name: {$this->bag}."
                );
            }
        } else {
            $this->bagDirectory = realpath($this->bag);
        }

        try {
            $this->bagitFile = $this->bagDirectory . '/bagit.txt';
            $bFileContents = $this->readFile($this->bagitFile);

            $versions = $this->parseVersionString($bFileContents);
            if ($versions === null) {
                throw new Exception();
            }
            $this->bagMajorVersion = $versions[0];
            $this->bagMinorVersion = $versions[1];

            $this->tagFileEncoding = $this->parseEncodingString($bFileContents);

        } catch (Exception $e) {
            array_push(
                $this->bagErrors,
                array('bagit', 'Error reading the bagit.txt file.')
            );
        }

        $ls = scandir($this->bagDirectory);
        if (count($ls) > 0) {
            $manifests = filterArrayMatches('/^manifest-(sha1|md5)\.txt$/', $ls);
            if (count($manifests) > 0) {
                $this->hashEncoding = strtolower($manifests[0][1]);
                $this->manifestFile = "{$this->bagDirectory}/{$manifests[0][0]}";
                $this->readManifestToArray();
            }

            $this->dataDirectory = "{$this->bagDirectory}/data";

            $manifests = filterArrayMatches(
                '/^tagmanifest-(sha1|md5)\.txt$/',
                $ls
            );
            if (count($manifests) > 0) {
                $this->tagManifestFile = "{$this->bagDirectory}/{$manifests[0][0]}";
                $this->readManifestToArray('t');
            }

            if (file_exists("{$this->bagDirectory}/fetch.txt")) {
                $this->fetchFile = "{$this->bagDirectory}/fetch.txt";
                $this->readFetchToArray();
            }

            if (file_exists("{$this->bagDirectory}/bag-info.txt")) {
                $this->bagInfoFile = "{$this->bagDirectory}/bag-info.txt";
                $this->readBagInfoToArray();
            }
        }
    }

    /**
     * Create a new bag. This expects $bag to be set.
     *
     * @return void
     */
    private function createBag() 
    {
        $cwd = getcwd();

        mkdir($this->bag);
        $this->bagDirectory = realpath($this->bag);

        $this->dataDirectory = $this->bagDirectory . '/data';
        mkdir($this->dataDirectory);

        $versionId = "BagIt-Version: {$this->bagMajorVersion}.{$this->bagMinorVersion}\n";
        $encoding = "Tag-File-Character-Encoding: {$this->tagFileEncoding}\n";

        $this->bagitFile = $this->bagDirectory . '/bagit.txt';
        $this->manifestFile = $this->bagDirectory .
            "/manifest-{$this->hashEncoding}.txt";

        $this->writeFile($this->bagitFile, $versionId . $encoding);

        touch($this->manifestFile);
        $this->readManifestToArray();

        if ($this->extended) {
            $this->tagManifestFile = $this->bagDirectory .
                "/tagmanifest-{$this->hashEncoding}.txt";

            touch($this->tagManifestFile);
            $this->readManifestToArray('t');

            $this->fetchFile = $this->bagDirectory . '/fetch.txt';
            touch($this->fetchFile);
            $this->readFetchToArray();

            $this->bagInfoFile = $this->bagDirectory . '/bag-info.txt';
            touch($this->bagInfoFile);
            $this->readBagInfoToArray();
        }
    }

    /**
     * This takes a file name and makes it relative to the bag directory.
     *
     * This is unsafe, strictly speaking, because it doesn't check that the 
     * file name passed in is in fact under the bag directory.
     *
     * @param string $filename An absolute file name under the bag directory.
     * @return string The file name relative to the bag directory.
     */
    private function makeRelative($filename)
    {
        return substr($filename, strlen($this->bagDirectory) + 1);
    }

    /**
     * This takes a list of files and generates an array mapping the file names 
     * (made relative to the data directory) to the hashes.
     *
     * @param array $files A list of absolute file names to generate hashes 
     * for.
     * @return array A mapping of relative file names to hashes.
     */
    private function makeChecksumArray($files)
    {
        $csums = array();

        foreach ($files as $file)
        {
            if (file_exists($file))
            {
                $hash = $this->calculateChecksum($file);
                $csums[$this->makeRelative($file)] = $hash;
            }
        }

        return $csums;
    }

    /**
     * This writes a checksum array to a file.
     *
     * @param array $csums     The checksum array to write.
     * @param string $filename The name of the file to write the checksums to.
     */
    private function writeChecksumArray($csums, $filename)
    {
        ksort($csums);
        $output = array();

        foreach ($csums as $path => $hash)
        {
            array_push($output, "$hash $path\n");
        }

        $this->writeFile($filename, implode('', $output));
    }

    /**
     * Create the checksum for a file.
     * 
     * @param string $filename The file to generate a checksum for.
     *
     * @return string The checksum.
     */
    private function calculateChecksum($filename) 
    {
        return hash_file($this->hashEncoding, $filename);
    }

    /**
     * This reads the manifest file into manifestContents.
     * 
     * @param string $mode The type of manifest to read. <code>t</code> means 
     * reading a tagmanifest file. Default is <code>d</code>.
     *
     * @return void
     */
    private function readManifestToArray($mode='d') 
    {
        if ($this->hashEncoding == 'sha1') {
            $hashLen = 40;
        } else {
            $hashLen = 32;
        }

        $this->updateManifestFileNames();

        if ($mode == 'd') {
            $filename = $this->manifestFile;
        } else if ($mode == 't') {
            $filename = $this->tagManifestFile;
        }

        try {
            $lines = $this->readLines($filename);

            $manifest = array();
            foreach ($lines as $line) {
                $hash = substr($line, 0, $hashLen);
                $payload = trim(substr($line, $hashLen));

                if (count($payload) > 0) {
                    $manifest[$payload] = $hash;
                }
            }

            if ($mode == 'd') {
                $this->manifestContents = $manifest;
            } else {
                $this->tagManifestContents = $manifest;
            }

        } catch (Exception $e) {
            array_push(
                $this->bagErrors,
                array('manifest', "Error reading $filename.")
            );
        }
    }

    /**
     * This reads the fetch.txt file into an array list.
     *
     * This sets $this->fetchContents to a sequential array of arrays with the 
     * keys 'url', 'length', and 'filename'.
     *
     * @return void
     */
    private function readFetchToArray() 
    {
        $lines = $this->readLines($this->fetchFile);
        $fetch = array();

        try {
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
            $this->fetchContents = $fetch;

        } catch (Exception $e) {
            array_push(
                $this->bagErrors,
                array('fetch', 'Error reading fetch file.')
            );
        }
    }

    /**
     * This writes the data in fetchContents into fetchFile.
     *
     * @return void
     */
    private function writeArrayToFetch() 
    {
        $lines = array();

        foreach ($this->fetchContents as $fetch) {
            $data = array($fetch['url'], $fetch['length'], $fetch['filename']);
            array_push($lines, join(' ', $data) . "\n");
        }

        $this->writeFile($this->fetchFile, join('', $lines));
    }

    /**
     * This reads the bag-info.txt file into an array dictionary.
     *
     * @return void
     */
    private function readBagInfoToArray() 
    {
        $lines = $this->readLines($this->bagInfoFile);
        $bagInfo = array();

        try {
            $prevKey = null;
            foreach ($lines as $line) {
                if (count($line) == 0) {
                    // Skip.
                } else if ($line[0] == ' ' || $line[1] == '\t') {
                    // Continued line.
                    $val = $bagInfo[$prevKey] . ' ' . trim($line);
                    $keys = array(
                        $prevKey,
                        strtolower($prevKey),
                        strtoupper($prevKey)
                    );
                    foreach ($keys as $pk) {
                        $bagInfo[$pk] = $val;
                    }
                } else {
                    list($key, $val) = preg_split('/:\s*/', $line, 2);
                    $val = trim($val);
                    $bagInfo[$key] = $val;
                    $bagInfo[strtolower($key)] = $val;
                    $bagInfo[strtoupper($key)] = $val;
                    $prevKey = $key;
                }
            }

            $this->bagInfoContents = $bagInfo;

        } catch (Exception $e) {
            array_push(
                $this->bagErrors,
                array('baginfo', 'Error reading bag info file.')
            );
        }
    }

    /**
     * Tests if a bag is compressed
     *
     * @return True if this is a compressed bag.
     */
    private function isCompressed() 
    {
        if (is_dir($this->bag)) {
            return false;
        } else {
            $bag = strtolower($this->bag);
            if (endsWith($bag, '.zip')) {
                $this->bagCompression = 'zip';
                return true;
            } else if (endsWith($bag, '.tar.gz') || endsWith($bag, '.tgz')) {
                $this->bagCompression = 'tgz';
                return true;
            }
        }
        return false;
    }

    /**
     * This makes sure that the manifest file names have the correct encoding.
     *
     * @return void
     */
    private function updateManifestFileNames() 
    {
        $this->manifestFile = "{$this->bagDirectory}/manifest-{$this->hashEncoding}.txt";
        $this->tagManifestFile = "{$this->bagDirectory}/tagmanifest-{$this->hashEncoding}.txt";
    }

    /**
     * This uncompresses a bag.
     *
     * @param string $bagBase The base name for the Bag It directory.
     *
     * @return The bagDirectory.
     */
    private function uncompressBag($bagBase) 
    {
        $dir = tempnam(sys_get_temp_dir(), 'bagit_');
        unlink($dir);
        mkdir($dir, 0700);

        if ($this->bagCompression == 'zip') {
            $zip = new ZipArchive();
            $zip->open($this->bag);
            $zip->extractTo($dir);

        } else if ($this->bagCompression == 'tgz') {
            $tar = new Archive_Tar($this->bag, 'gz');
            $tar->extract($dir);

        } else {
            throw new BagItException(
                "Invalid bag compression format: {$this->bagCompression}."
            );
        }

        return "$dir/$bagBase";
    }

    /**
     * This compresses the bag into a new file.
     *
     * @param string $method Either 'tgz' or 'zip'. Default is 'tgz'.
     *
     * @return string The file name for the file.
     */
    private function compressBag($method='tgz') 
    {
        $output = tempnam(sys_get_temp_dir(), 'bagit_');
        unlink($output);

        $base = basename($this->bagDirectory);
        $stripLen = strlen($this->bagDirectory) - strlen($base);

        if ($method == 'zip') {
            $zip = new ZipArchive();
            $zip->open($output, ZIPARCHIVE::CREATE);

            foreach (rls($this->bagDirectory) as $file) {
                $zip->addFile($file, substr($file, $stripLen));
            }

            $zip->close();

        } else if ($method == 'tgz') {
            $tar = new Archive_Tar($output, 'gz');
            $tar->createModify(
                $this->bagDirectory,
                $base,
                $this->bagDirectory
            );

        }

        return $output;
    }

    /**
     * This parses the version string from the bagit.txt file.
     *
     * @param string $bagitFileContents The contents of the bagit file.
     *
     * @return A two-item array containing the version string as integers.
     */
    private function parseVersionString($bagitFileContents) 
    {
        $matches = array();
        $success = preg_match(
            "/BagIt-Version: (\d+)\.(\d+)/i",
            $bagitFileContents,
            $matches
        );

        if ($success) {
            $major = (int)$matches[1];
            $minor = (int)$matches[2];
            if ($major === null || $minor === null) {
                throw new Exception("Invalid bagit version: '{$matches[0]}'.");
            }
            return array($major, $minor);
        }
    }

    /**
     * This parses the encoding string from the bagit.txt file.
     *
     * @param string $bagitFileContents The contents of the bagit file.
     *
     * @return The encoding.
     */
    private function parseEncodingString($bagitFileContents)
    {
        $matches = array();
        $success = preg_match(
            '/Tag-File-Character-Encoding: (.*)/i',
            $bagitFileContents,
            $matches
        );

        if ($success) {
            return $matches[1];
        }
    }

    /**
     * This cleans up the file name.
     *
     * @param string $filename The file name to clean up.
     *
     * @return string The cleaned up file name.
     */
    private function sanitizeFileName($filename) 
    {
        // White space => underscores.
        $filename = preg_replace('/\s+/', '_', $filename);

        // Remove some characters.
        $filename = preg_replace(
            '/\.{2}|[~\^@!#%&\*\/:\'?\"<>\|]/',
            '',
            $filename
        );

        $forbidden = '/^(CON|PRN|AUX|NUL|COM1|COM2|COM3|COM4|COM5| ' .
            'COM6|COM7|COM8|COM9|LPT1|LPT2|LPT3|LPT4|LPT5|LPT6|' .
            'LPT7|LPT8|LPT9)$/';

        if (preg_match($forbidden, $filename)) {
            $filename = strtolower($filename);
            $suffix = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 12);
            $filename = "{$filename}_{$suffix}";
        }

        return $filename;
    }
    //}}}

}

/* Functional wrappers/facades. */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */


?>
