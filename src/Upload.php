<?php declare(strict_types=1);

namespace Compolomus\BinaryFileStorage;

class Upload
{
    protected $tmpDir;

    public function __construct(string $dir)
    {
        $this->tmpDir = $dir . DIRECTORY_SEPARATOR . 'tmp';
    }

    public function process($files)//: array
    {
        $files = $this->upload($files);
        return $this->addtemp($files);
    }

    private function addTemp(array $array): array
    {
        $result = [];
        foreach ($array as $key => $file) {
            if (!empty($file['tmp_name'])) {
                $data = [
                    'tmp' => $file['tmp_name'],
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size']
                ];
                $obj = new File($data);
                $fileName = $this->tmpDir . DIRECTORY_SEPARATOR . $obj->getMd5();
                $obj->setPath($fileName);
                \rename($file['tmp_name'], $fileName);
                $result[] = $obj;
            }
        }
        return $result;
    }

    /**
     * $inputArray => $_FILES
     * @param array $inputArray
     * @return array
     */
    private function upload(array $inputArray): array
    {
        $result = [];
        foreach ($inputArray as $fieldName => $file) {
            foreach ($file as $key => $value) {
                if (!is_array($value)) {
                    $result[$fieldName][$key] = $value;
                } else {
                    foreach ($value as $k => $v) {
                        $result[$k][$key] = $v;
                    }
                }
            }
        }
        return $result;
    }
}
