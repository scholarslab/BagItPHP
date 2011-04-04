<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This is a PHP implementation of the {@link 
 * https://wiki.ucop.edu/display/Curation/BagIt BagIt specification}. Really, 
 * it is a port of {@link https://github.com/ahankinson/pybagit/ PyBagIt} for 
 * PHP. This contains some useful functions.
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



/**
 * This filters an array by items that match a regex.
 *
 * @param string $regex The regex to filter by.
 * @param array  $list  The list of items to filter.
 * 
 * @return The match objects for items from $list that match $regex.
 */
function filterArrayMatches($regex, $list) 
{
    $ret = array();

    foreach ($list as $item) {
        $matches = array();
        if (preg_match($regex, $item, $matches)) {
            array_push($ret, $matches);
        }
    }

    return $ret;
}

/**
 * This tests whether a string ends with another string.
 *
 * @param string $main   The primary string to test.
 * @param string $suffix The string to test against the end of the other.
 * 
 * @return True if $suffix occurs at the end of $main.
 */
function endsWith($main, $suffix) 
{
    $len = strlen($suffix);
    return substr_compare($main, $suffix, -$len, $len) === 0;
}

/**
 * This recursively lists the contents of a directory. This doesn't return 
 * hidden files.
 * 
 * @param string $dir The name of the directory to list.
 * 
 * @return array A list of files in the directory.
 */
function rls($dir) 
{
    $ls = array();
    $queue = array($dir);

    while (count($queue) > 0) {
        $current = array_shift($queue);

        foreach (scandir($current) as $item) {
            if ($item[0] != '.') {
                $filename = "$current/$item";

                switch (filetype($filename)) {
                case 'file':
                    array_push($ls, $filename);
                    break;
                case 'dir':
                    array_push($queue, $filename);
                    break;
                }
            }
        }
    }

    return $ls;
}

/**
 * Recursively delete a directory.
 */
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir")
                    rrmdir($dir . "/" . $object);
                else
                    unlink($dir . "/" . $object);
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

/**
 * Get a temporary name and create a directory there.
 */
function tmpdir($prefix='bag') {
    $dir = tempnam(sys_get_temp_dir(), $prefix);
    unlink($dir);
    return $dir;
}

/**
 * This tests whether the item is in a list of lists at the given key.
 * @param array $array The array of arrays to search.
 * @param integer/string $key The key to search under.
 * @param anything $item The item to search for.
 * @return True if $item is in a subarray under $key.
 */
function seenAtKey($array, $key, $item) {
    $keys = array_keys($array);
    for ($x=0, $len=count($keys); $x<$len; $x++) {
        $sub = $array[$keys[$x]];
        if ($sub[$key] == $item) {
            return true;
        }
    }
    return false;
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */

?>
