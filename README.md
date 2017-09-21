# Koenig BinaryFileStorage

[![License](https://poser.pugx.org/compolomus/binary-file-storage/license)](https://packagist.org/packages/compolomus/binary-file-storage)

[![Build Status](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/build-status/master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Compolomus/BinaryFileStorage/?branch=master)
[![Code Climate](https://codeclimate.com/github/Compolomus/BinaryFileStorage/badges/gpa.svg)](https://codeclimate.com/github/Compolomus/BinaryFileStorage)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/2b860c2a-a573-45aa-9e33-d597d5907bc0/mini.png)](https://insight.sensiolabs.com/projects/2b860c2a-a573-45aa-9e33-d597d5907bc0)
[![Downloads](https://poser.pugx.org/compolomus/binary-file-storage/downloads)](https://packagist.org/packages/compolomus/binary-file-storage)

## Перехват выгрузки файлов с форм, добавление в хранилище в бинарном виде, получение файлов по хэш ключу

## Установка:

composer require compolomus/binary-file-storage

## Применение:

```php

use Compolomus\BinaryFileStorage\Storage;

require __DIR__ . '/vendor/autoload.php';

$storageConfig = [
    'uploadDir' => 'files',
    'prefix' => 'prefix',
    'firstDirLen' => 1,
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
        $meta = $storage->getInfoFile($file);
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
