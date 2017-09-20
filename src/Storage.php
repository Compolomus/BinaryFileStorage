<?php declare(strict_types=1);

namespace Compolomus\BinaryFileStorage;

class Storage
{
    protected $dir;

    protected $count;

    protected $config;

    protected $upload;

    public function __construct(
        string $dir,
        array $config = [
            'uploadDir' => 'upload',
            'prefix' => '',
            'addMetaFile' => true,
            'firstDirLen' => 2,
            'secondDirLen' => 2
        ]
    ) {
        $this->config = $config;
        $this->dir = $this->createStorageDir($config['uploadDir'] . DIRECTORY_SEPARATOR . $config['prefix']);
        $this->upload = new Upload($this->dir);
        $this->count = \count($this->map());
    }

    private function createStorageDir(string $dir): string
    {
        if (!is_dir($dir)) {
            \mkdir(\rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'tmp', 0750, true);
        }
        return $dir;
    }

    public function check(array $input): ?array
    {
        if (\count($input)) {
            return $this->add($this->upload->process($input));
        } else {
            return null;
        }
    }

    public function count(): int
    {
        return $this->count;
    }

    public function lastInsert(int $files = 1, int $offset = 0): ?\LimitIterator
    {
        return ($this->count >= $files ? new \LimitIterator($this->map(), $offset, $files) : null);
    }

    public function download(string $file): ?\SplFileObject
    {
        $fileName = \is_file($file) ? $file : $this->dirName($file) . DIRECTORY_SEPARATOR . $file;
        return \is_file($fileName) ? new \SplFileObject($fileName) : null;
    }

    /**
     * @param $file File | array
     */
    private function add($file): array
    {
        static $result = [];
        if (!is_array($file)) {
            $data = $file->getData();
            $md5 = $data['md5'];
            $dir = $this->createDir($md5);
            $item = $dir . DIRECTORY_SEPARATOR . $md5;
            \rename($data['path'], $item);
            $meta = [
                'name' => $data['name'],
                'ext' => $data['ext'],
                'size' => $data['size'],
                'type' => $data['type'],
                'path' => $item,
                'bin' => $md5
            ];
            if ($this->config['addMetaFile']) {
                \file_put_contents($item . '.json',
                    json_encode($meta));
            }
            $result[] = $meta;
        } else {
            array_map([$this, 'add'], $file, [$this->config['addMetaFile']]);
        }
        return $result;
    }

    public function fileSize(int $size): string
    {
        $alpha = [" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"];
        return $size ? \round($size / \pow(1024, ($i = \floor(\log($size, 1024)))), 2) . $alpha[$i] : '0 Bytes';
    }

    public function dirName(string $file): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . \substr($file, 0,
                $this->config['firstDirLen']) . DIRECTORY_SEPARATOR . \substr($file, $this->config['firstDirLen'],
                $this->config['secondDirLen']);
    }

    public function getInfoFile(string $file): ?array
    {
        $fileName = $this->dirName($file) . DIRECTORY_SEPARATOR . $file . '.json';
        return \is_file($fileName) ? \json_decode(\file_get_contents($fileName), true) : null;
    }

    public function map(): \ArrayIterator
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir),
            \RecursiveIteratorIterator::CHILD_FIRST) as $fullFileName => $SplFileObject) {
            if ($SplFileObject->isFile() && $SplFileObject->getExtension() != 'json') {
                $files[\basename($fullFileName)] = [
                    'file' => $SplFileObject->getRealPath(),
                    'time' => $SplFileObject->getMTime()
                ];
            }
            \uasort($files, [$this, 'sortByTime']);
        }
        return new \ArrayIterator($files);
    }

    private function sortByTime(array $a, array $b): int
    {
        return ($a['time'] <=> $b['time']);
    }

    private function createDir(string $md5): string
    {
        $dir = $this->dirName($md5);
        if (!is_dir($dir)) {
            \mkdir($dir . DIRECTORY_SEPARATOR, 0750, true);
        }
        return $dir;
    }
}