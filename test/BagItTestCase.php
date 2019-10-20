<?php
/**
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless
 * required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied. See the License for the
 * specific language governing permissions and limitations under the License.
 */

namespace ScholarsLab\BagIt\Tests;

use PHPUnit\Framework\TestCase;
use ScholarsLab\BagIt\BagItUtils;

/**
 * Base testing class to add some assert functions.
 * @package ScholarsLab\BagIt\Tests\TestBag
 */
class BagItTestCase extends TestCase
{
    /**
     * Location of TestBag directory, should be copied to a temporary directory for testing.
     */
    const TEST_BAG_DIR = __DIR__ . "/TestBag";

    /**
     * Location of TestBag zip.
     */
    const TEST_BAG_ZIP = __DIR__ . "/TestBag.zip";

    /**
     * Location of TestBag tgz.
     */
    const TEST_BAG_TGZ = __DIR__ . "/TestBag.tgz";


    /**
     * Copy the TestBag directory to a temporary directory so we can destroy it after each test.
     *
     * @return string The temporary directory with the copy of the test bag.
     */
    protected function prepareTestBagDirectory()
    {
        $tmp = BagItUtils::tmpdir();
        mkdir($tmp);
        $this->copyDir(self::TEST_BAG_DIR, $tmp);
        return $tmp;
    }

    /**
     * Compare two arrays have all the same elements, does not compare order.
     *
     * @param array $expected The expected array.
     * @param array $testing The array to test.
     */
    protected function assertArrayEquals(array $expected, array $testing)
    {
        // They have the same number of elements
        $this->assertCount(count($expected), $testing);
        // All the elements in $expected exist in $testing
        $this->assertCount(0, array_diff($expected, $testing));
        // All the elements in $testing exist in $expected (possibly overkill)
        $this->assertCount(0, array_diff($testing, $expected));
    }

    /**
     * Recursively copy the directory from src to dest
     *
     * @param string $src The original directory.
     * @param string $dest The destination directory.
     */
    private function copyDir($src, $dest)
    {
        foreach (scandir($src) as $item) {
            if ($item == "." || $item == "..") {
                continue;
            }
            if (is_dir("{$src}/{$item}")) {
                if (!is_dir("{$dest}/{$item}")) {
                    mkdir("{$dest}/{$item}");
                }
                $this->copyDir("{$src}/{$item}", "{$dest}/{$item}");
            } else {
                copy("{$src}/{$item}", "{$dest}/{$item}");
            }
        }
    }
}
