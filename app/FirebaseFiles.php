<?php

namespace App;

class FirebaseFiles
{

    private  $bucket;

    public function __construct() {
        $this->bucket = app('firebase.storage')->getBucket();
    }

    /**
         * Upload File
         *
         * @param File   $file  File to upload
         * @param string $name File name
         * @param string $path Path to save file
         *
         * @author Miguel Plascencia
    */
    public function upload($file, $name, $path) {
        $uri = $path . "/" . $name;
        $this->bucket
        ->upload(fopen($file, 'r'), [
            'name' => $uri
        ]);
        return $uri;
    }

    /**
         * Download File
         *
         * @param string $fileName File name
         *
         * @author Miguel Plascencia
    */
    public function download($fileName) {
        $expiresAt = new \DateTime('tomorrow');
        $stream = $this->bucket
        ->object($fileName)
        ->signedUrl($expiresAt);
        $file = tempnam(sys_get_temp_dir(), 'test.png');
        copy($stream, $file);

        return $file;
    }

     /**
         * Get File List From Path
         *
         * @param string $path Folder path
         *
         * @author Miguel Plascencia
    */
    public function fileList($path) {
        $options = ['prefix' => $path];

        $files = collect($this->bucket->objects($options))->map(function($file) {
            return $file->name();
        });

        return $files;
    }

    /**
         * Delete File
         *
         * @param string $path File path
         *
         * @author Miguel Plascencia
    */
    public function delete($path) {
        $this->bucket
        ->object($path)
        ->delete();
    }

}
