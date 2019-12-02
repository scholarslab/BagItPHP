### Notes

When using BagItPHP you should be aware.

1. Tags order is not maintained.
1. Destination paths are not validated. (ie. `data/../../..//var/lib`) 
1. To properly add a fetch file to your bag you should:
    1. Add the Fetch URL (`$bag->fetch->add('http://something', 'data/destination')`).
    1. Download the fetch files (`$bag->fetch->download()`).
    1. Update the bag (`$bag->update()`)
    1. Manually delete the downloaded files from the bag.
    1. Package your bag (`$bag->package('somefile', 'zip')`).
1. Tag Manifests do not include any tag files files other than `bagit.txt`, `bag-info.txt`, `fetch.txt` and 
`manifest-(sha1|md5).txt`
1. Fetch files are not validated as part of the bag.
