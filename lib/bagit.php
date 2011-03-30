<?php

/**
 * This is a PHP implementation of the {@link 
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really, 
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for 
 * PHP.
 *
 * @package bagit
 * @author Eric Rochester (erochest@gmail.com)
 * @copyright 2011
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version 0.1
 * @link https://github.com/erochest/BagItPHP
 */


/**
 * This is the main class for interacting with a bag.
 * @package bagit
 */
class BagIt {

    /**
     * The bag as passed into the constructor. This could be a directory or a 
     * file name, and it may not exist.
     * @var string
     */
    var $bag;

    /**
     * Absolute path to the bag directory.
     * @var string
     */
    var $bagDirectory;

    /**
     * True if the bag is extended.
     * @var boolean
     */
    var $extended;

    /**
     * 'sha1' or 'md5'. Default is 'sha1'.
     * @var string
     */
    var $hashEncoding;

    /**
     * The major version number declared in 'bagit.txt'. Default is '0'.
     * @var string
     */
    var $bagMajorVersion;

    /**
     * The minor version number declared in 'bagit.txt'. Default is '96'.
     * @var string
     */
    var $bagMinorVersion;

    /**
     * The tag file encoding declared in 'bagit.txt'. Default is 'utf-8'.
     * @var string
     */
    var $tagFileEncoding;

    /**
     * Absolute path to the data directory.
     * @var string
     */
    var $dataDirectory;

    /**
     * Absolute path to the bagit file.
     * @var string
     */
    var $bagitFile;

    /**
     * Absolute path to the 'manifest-{sha1,md5}.txt' file.
     * @var string
     */
    var $manifestFile;

    /**
     * Absolute path to the 'tagmanifest-{sha1,md5}.txt' file or null.
     * @var string
     */
    var $tagManifestFile;

    /**
     * Absolute path to the 'fetch.txt' file or null.
     * @var string
     */
    var $fetchFile;

    /**
     * Absolute path to the 'bag-info.txt' file or null.
     * @var string
     */
    var $bagInfoFile;

    /**
     * A dictionary array containing the manifest file contents.
     * @var array
     */
    var $manifestContents;

    /**
     * A dictionary array containing the tagmanifest file contents.
     * @var array
     */
    var $tagManifestContents;

    /**
     * A dictionary array containing the 'fetch.txt' file contents.
     * @var array
     */
    var $fetchContents;

    /**
     * A dictionary array containing the 'bag-info.txt' file contents.
     * @var array
     */
    var $bagInfoContents;

    /**
     * If the bag came from a compressed file, this contains either 'tgz' or 
     * 'zip' to indicate the file's compression format.
     * @var string
     */
    var $bagCompression;

    /**
     * An array of all bag validation errors. Each entries is a two-element array 
     * containing the path of the file and the error message.
     * @var array
     */
    var $bagErrors;

    /**
     * Define a new BagIt instance.
     *
     * @param string $bag Either a non-existing folder name (will create a new 
     * bag here); an existing folder name (this will treat it as a bag and 
     * create any missing files or folders needed); or an existing compressed 
     * file (this will un-compress it to a temporary directory and treat it as 
     * a bag).
     *
     * @param boolean $validate This will validate all files in the bag, 
     * including running checksums on all of them. Default is false.
     *
     * @param boolean $extended This will ensure that optional 'bag-info.txt', 
     * 'fetch.txt', and 'tagmanifest-{sha1,md5}.txt' are created. Default is 
     * true.
     *
     * @param boolean $fetch If true, it will download all files in 
     * 'fetch.txt'. Default is false.
     */
    public function BagIt($bag, $validate=false, $extended=true, $fetch=false) {
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
            return;
        } else {
            $this->createBag();
        }

        if ($validate) {
            $this->validate();
        }
    }

    /**
     * Open an existing bag. This expects $bag to be set.
     */
    private function openBag() {
    }

    /**
     * Create a new bag. This expects $bag to be set.
     */
    private function createBag() {
    }

    /**
     * @return boolean True if no validation errors occurred.
     */
    public function isValid() {
        return (count($this->bagErrors) == 0);
    }

    /**
     * @return boolean True if the bag contains the optional files 
     * 'bag-info.txt', 'fetch.txt', or 'tagmanifest-{sha1,md5}.txt'.
     */
    function isExtended() {
    }

    /**
     * @return array A dictionary array containing these keys: 'version', 
     * 'encoding', 'hash'.
     */
    function getBagInfo() {
    }

    /**
     * @return string The absolute path to the bag's data directory.
     */
    function getDataDirectory() {
    }

    /**
     * @return string The bag's checksum encoding scheme.
     */
    function getHashEncoding() {
    }

    /**
     * Sets the bag's checksum hash algorithm.
     * @param string $hashAlgorithm The bag's checksum hash algorithm. Must be 
     * either 'sha1' or 'md5'.
     */
    function setHashEncoding($hashAlgorithm) {
    }

    /**
     * Prints a number of bag properties.
     */
    function showBagInfo() {
    }

    /**
     * @return array An array of absolute paths for all of the files in the data 
     * directory.
     */
    function getBagContents() {
    }

    /**
     * @param boolean $validate If true, then it will run this->validate() to 
     * verify the integrity first. Default is false.
     * @return array An array of all bag errors.
     */
    function getBagErrors($validate=false) {
    }

    /**
     * Runs the bag validator on the contents of the bag. This verifies the presence of required 
     * files and folders and verifies the checksum for each file.
     *
     * For the results of validation, check isValid() and getBagErrors().
     */
    function validate() {
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
     */
    function update() {
    }

    /**
     * Downloads every entry in 'fetch.txt'.
     * @param boolean $validate If true, then it also calls update() and 
     * validate().
     */
    function fetch($validate=false) {
    }

    /**
     * Writes new entries in 'fetch.txt'.
     *
     * @param array $fetchEntries An array containing the URL and path relative to 
     * the data directory for file.
     * @param boolean $append If false, the current entries in 'fetch.txt' will 
     * be overwritten. Default is true.
     */
    function addFetchEntries($fetchEntries, $append=true) {
    }

    /**
     * Compresses the bag into a file.
     *
     * @param string $destination The file to put the bag into.
     * @param string $method Either 'tgz' or 'zip'. Default is 'tgz'.
     */
    function package($destination, $method) {
    }

}

/* Functional wrappers/facades. */

?>
