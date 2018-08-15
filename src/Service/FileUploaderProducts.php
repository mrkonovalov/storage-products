<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Сохранение файла в папку
 *
 * Class FileUploaderProducts
 *
 * @package App\Service
 */
class FileUploaderProducts
{
    private $targetDirectory;
    
    /**
     * FileUploaderProducts constructor.
     *
     * @param $targetDirectory
     */
    public function __construct($targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }
    
    /**
     * Не идеальное решение конечно, но для текущей задачи вполне пойдет
     * Не идеальное потому что:
     * 1. Теряется имя файла
     *
     * @param UploadedFile $file
     * @return string
     */
    public function upload(UploadedFile $file): string
    {
        $fileName = md5(uniqid('', true)).'.'.$file->getClientOriginalExtension();
        
        $file->move($this->getTargetDirectory(), $fileName);
        
        return $fileName;
    }
    
    /**
     * @return mixed
     */
    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
