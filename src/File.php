<?php declare(strict_types=1);

namespace Compolomus\BinaryFileStorage;

class File
{
    protected $name;

    protected $size;

    protected $ext;

    protected $type;

    protected $md5;

    protected $path;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->md5 = $this->generateMd5Hash($data['tmp']);
        $this->size = $data['size'];
        $this->ext = 'bin';
        if (\preg_match("#\.#", $data['name'])) {
            $ext = \explode('.', $data['name']);
            $this->ext = \end($ext);
        }
        $this->type = $data['type'];
    }

    public function getMd5(): string
    {
        return $this->md5;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getData(): array
    {
        return [
            'name' => $this->name,
            'md5' => $this->md5,
            'size' => $this->size,
            'ext' => $this->ext,
            'type' => $this->type,
            'path' => $this->path
        ];
    }

    public function generateMd5Hash(string $file): string
    {
        return \md5_file($file);
    }
}
