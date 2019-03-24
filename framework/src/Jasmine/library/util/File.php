<?php
/**
 * Created by PhpStorm.
 * User: zzpzero
 * Date: 2017/12/29
 * Time: 12:25
 */

namespace Jasmine\library\util;

class File
{

    /**
     * Create a directory.
     *
     * @param  string $path
     * @param  int $mode
     * @param  bool $recursive
     * @param  bool $force
     * @return bool
     */
    static public function mkDir($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        } else {
            return mkdir($path, $mode, $recursive);
        }
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param  string $directory
     * @param  bool $preserve
     * @return bool
     */
    static public function deleteDir($directory, $preserve = false)
    {
        if (!is_dir($directory)) return false;

        $items = new \FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-director, otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir()) {
                static::deleteDir($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                static::delete($item->getPathname());
            }
        }

        if (!$preserve) @rmdir($directory);

        return true;
    }

    /**
     * Delete the file at a given path.
     *
     * @param  string|array $paths
     * @return bool
     */
    static public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            if (!@unlink($path)) $success = false;
        }

        return $success;
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param  string $directory
     * @return array
     */
    static public function files($directory)
    {
        $glob = glob($directory . DIRECTORY_SEPARATOR . '*');

        if ($glob === false) return array();

        // To get the appropriate files, we'll simply glob the directory and filter
        // out any "files" that are not truly files so we do not end up with any
        // directories in our list, but only true files within the directory.
        return array_filter($glob, function ($file) {
            return filetype($file) == 'file';
        });
    }

    /**
     * @param $file
     * @param $contents
     */
    static public function write($file, $contents)
    {
        if (!is_dir(dirname($file))) {
            self::mkDir(dirname($file));
        }
        $fp = fopen($file, "w") or die("Unable to open file!");
        fwrite($fp, $contents);
        fclose($fp);
    }

    /**
     * @param $dir
     * @return bool
     */
    static public function isDir($dir)
    {
        return is_dir($dir);
    }

    /**
     * @param $file
     * @return bool
     */
    static public function exists($file)
    {
        return file_exists($file);
    }

    static function load($file){
        if(is_dir($file)){
            foreach (self::files($file) as $f) {
                __require_file($f);
            }
        }elseif (is_file($file)){
            __require_file($file);
        }
    }
}

/**
 * 作用范围隔离
 *
 * @param $file
 * @return mixed
 */

function __require_file($file)
{
    return require $file;
}
