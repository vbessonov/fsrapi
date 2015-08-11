<?php
/*
 * The MIT License
 *
 * Copyright 2015 Vyacheslav Bessonov <v.bessonov@hotmail.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace VBessonov\FSRAPI\IO;

/**
 * Description of DirectoryInfo
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class DirectoryInfo extends FileSystemInfo
{
    const FILE = 0x1;
    const DIRECTORY = 0x2;
    const ALL_DIRECTORIES = 0x1;
    const TOP_DIRECTORY_ONLY = 0x3;

    public function __construct($path)
    {
        parent::__construct($path);
    }

    private function getInfosHelper($currentPath, $infoKind, $searchOptions, &$infos)
    {
        $paths = array_diff(scandir($currentPath), array('..', '.'));

        foreach ($paths as $path) {
            $path = Path::combine($currentPath, $path);
            
            if (Path::isDir($path)) {
                if (($infoKind & self::DIRECTORY) != 0) {
                    $directoryInfo = new DirectoryInfo($path);
                    array_push($infos, $directoryInfo);
                }
                if ($searchOptions === self::ALL_DIRECTORIES) {
                    $this->getInfosHelper($path, $infoKind, $searchOptions, $infos);
                }
            } else {
                if (($infoKind & self::FILE) != 0) {
                    $fileInfo = new FileInfo($path);
                    array_push($infos, $fileInfo);
                }
            }
        }
    }

    public function getSize()
    {
        return 0;
    }

    public function getFiles($searchOptions = self::TOP_DIRECTORY_ONLY)
    {
        $files = [];

        $this->getInfosHelper($this->path, self::FILE, $searchOptions, $files);

        return $files;
    }

    public function getDirectories($searchOptions = self::TOP_DIRECTORY_ONLY)
    {
        $directories = [];

        $this->getInfosHelper($this->path, self::DIRECTORY, $searchOptions, $directories);

        return $directories;
    }

    public function getFileSystemInfos($searchOptions = self::TOP_DIRECTORY_ONLY)
    {
        $infos = [];

        $this->getInfosHelper($this->path, self::FILE | self::DIRECTORY, $searchOptions, $infos);

        return $infos;
    }

    public function getParent()
    {
        return new DirectoryInfo(dirname($this->path));
    }
}