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

use VBessonov\FSRAPI\Config;
use VBessonov\FSRAPI\Utils\DateTimeUtils;

/**
 * Description of FileSystemInfoSerializer
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class FileSystemInfoSerializer
{
    const WITHOUT_CONTENT = 0x1;
    const TOP_DIRECTORY_ONLY = 0x3;
    const ALL_DIRECTORIES = 0x5;
    
    private static function infoToJson(FileSystemInfo $info)
    {
        $isDir = $info instanceof DirectoryInfo;
        $virtualPath = Path::getVirtualPath($info->getFullName(), Config::getRootDir());
        $json = array(
            'name' => $info->getName(),
            'path' => strlen($virtualPath) > 1 ? rtrim($virtualPath, '/') : $virtualPath,
            'size' => $isDir ? 0 : $info->getSize(),
            'last_access_time' => DateTimeUtils::toString($info->getLastAccessTime()),
            'last_write_time' => DateTimeUtils::toString($info->getLastWriteTime()),
            'is_dir' => $isDir
        );
        
        return $json;
    }

    public static function toJson(FileSystemInfo $info, $searchOptions = self::WITHOUT_CONTENT)
    {
        $json = self::infoToJson($info);

        if ($searchOptions !== self::WITHOUT_CONTENT &&
            $info instanceof DirectoryInfo) {
            $directorySearchOptions =
                $searchOptions == self::TOP_DIRECTORY_ONLY
                ? DirectoryInfo::TOP_DIRECTORY_ONLY
                : DirectoryInfo::ALL_DIRECTORIES;
            $files = $info->getFileSystemInfos($directorySearchOptions);
            $contentsJsonArray = array();

            foreach ($files as $file) {
                $contentsJson = self::infoToJson($file);
                array_push($contentsJsonArray, $contentsJson);
            }

            $json['contents'] = $contentsJsonArray;
        }

        return $json;
    }
}