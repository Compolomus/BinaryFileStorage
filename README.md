# Koenig BinaryFileStorage

[![License](https://img.shields.io/badge/license-GPL%20v.3-blue.svg?style=plastic)](https://www.gnu.org/licenses/gpl-3.0-standalone.html)

[![Build Status](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/?branch=master)


## Установка:

composer require compolomus/***

## Применение:

```php

use Compolomus\BinaryFileStorage\Storage;

require __DIR__ . '/vendor/autoload.php';

$storageConfig = [
    'uploadDir' => 'files',
    'prefix' => 'prefix',
    'addMetaFile' => true,
    'firstDirLen' => 1,
    'secondDirLen' => 2
];

$storage = new Storage($storageConfig);
$input = $storage->check($_FILES);

/*
    Array
(
    [0] => Array
        (
            [name] => 22.png
            [ext] => png
            [size] => 35359
            [type] => image/png
            [path] => files\prefix\7\26\726cd0cefcc522784b2f317ca0affe5f
            [bin] => 726cd0cefcc522784b2f317ca0affe5f
        )

    [1] => Array
        (
            [name] => putty.exe
            [ext] => exe
            [size] => 454656
            [type] => application/x-msdownload
            [path] => files\prefix\9\bb\9bb6826905965c13be1c84cc0ff83f42
            [bin] => 9bb6826905965c13be1c84cc0ff83f42
        )

)
*/

// insert into files ->execute([$input]);

$limit = isset($_GET['limit']) ? abs(intval($_GET['limit'])) : 0;
$file = isset($_GET['file']) ? htmlentities($_GET['file'], ENT_QUOTES, 'UTF-8') : false;
if ($file) {
    $obj = $storage->download($file);
    if (!is_null($obj)) {
        $meta = $storage->getInfoFile($file, 'rb');
        ob_get_level() && ob_end_clean();
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 OK');
        header('Content-Type: application/force-download');
        header('Content-Description: inline; File Transfer');
        header('Content-Transfer-Encoding: binary');
        if (array_key_exists('name', $meta) & array_key_exists('size', $meta)) {
            header('Content-Disposition: attachment; filename="' . $meta['name'] . '";', false);
            header('Content-Length: ' . $meta['size']);
        }

        $speed = 1024 * $limit;

        if ($speed > 0) {
            $sleep = 1;
        } else {
            $speed = 8 * 1024;
            $sleep = 0;
        }

        while (!$obj->eof()) {
            $buf = $obj->fread($speed);
            print($buf);
            if ($sleep) {
                sleep(1);
            }
            flush();
        }
        exit;
    } else {
        echo '<h1>File not found</h1>';
        exit;
    }
}

?>
    <form action="?" enctype="multipart/form-data" method="post">
        <div><input type="file" name="file[]"/></div>
        <div><input type="file" name="file[]"/></div>
        <div><input type="file" name="file[]"/></div>
        <div><input type="submit" name="submit" value="submit"/></div>
    </form>
<?php

$count = $storage->count();

if ($count) {
    $i = 0;
    $files = $count > 10 ? 10 : $count;
    $result = $storage->lastInsert($files);

    foreach ($result as $key => $value) {
        $data = $storage->getInfoFile($key);
        echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
            . ($data ? '<h3>' . $data['name'] . ' ( ' . $storage->fileSize($data['size']) . ' )</h3>' : '')
            . '<div class="sub green">
                    <a href="?file=' . $key . '">Download full speed</a> | 
                    <a href="?file=' . $key . '&amp;limit=128">Download 128 kbps</a> | 
                    <a href="?file=' . $key . '&amp;limit=256">Download 256 kbps</a> 
                </div>
            </div>';
    }
}

#echo '<pre>' . print_r($storage->map(), true) . '</pre>';

```
