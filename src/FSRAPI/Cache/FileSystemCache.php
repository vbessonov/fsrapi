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

namespace VBessonov\FSRAPI\Cache;

use Doctrine\Common\Cache\Cache;
use VBessonov\FSRAPI\Config;
use VBessonov\FSRAPI\IO\DirectoryInfo;
use VBessonov\FSRAPI\IO\FileInfo;
use VBessonov\FSRAPI\IO\FileSystemInfo;
use VBessonov\FSRAPI\IO\Path;

/**
 * Description of FileSystemCache
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class FileSystemCache
{
    const WITHOUT_CONTENT = 0x1;
    const TOP_DIRECTORY_ONLY = 0x3;

    private $cache;
    
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    private function getCachedTimestamp(FileSystemInfo $info)
    {
        $timestamp = $this->cache->fetch($info->getFullName());

        return $timestamp;
    }

    private function getNativeTimestamp(FileSystemInfo $info, $searchOptions = self::WITHOUT_CONTENT)
    {
        if ($info instanceof FileInfo) {
            $timestamp = $info->getLastWriteTime();

            $this->cache->save($info->getFullName(), $timestamp);

            return $timestamp;
        } else {
            $directorySearchOptions =
                $searchOptions == self::WITHOUT_CONTENT
                ? DirectoryInfo::TOP_DIRECTORY_ONLY
                : DirectoryInfo::ALL_DIRECTORIES;
            $innerInfos = $info->getFileSystemInfos($directorySearchOptions);
            $timestamp = $info->getLastWriteTime();

            foreach ($innerInfos as $innerInfo) {
                $innerTimestamp = $this->getTimestampHelper($innerInfo, $searchOptions);
                $timestamp = max($innerTimestamp, $timestamp);
            }

            $this->cache->save($info->getFullName(), $timestamp);

            return $timestamp;
        }
    }

    private function getTimestampHelper(FileSystemInfo $info, $searchOptions = self::WITHOUT_CONTENT)
    {
        $timestamp = $this->getCachedTimestamp($info);

        if ($timestamp !== false) {
            return $timestamp;
        }

        return $this->getNativeTimestamp($info, $searchOptions);
    }

    private function setTimestampHelper(FileSystemInfo $info, $timestamp)
    {
        $parentInfo = $info;
        $rootDirInfo = new DirectoryInfo(Config::getRootDir());
        $endDirPath = $rootDirInfo->getParent()->getFullName();
        
        do {
            $this->cache->save($parentInfo->getFullName(), $timestamp);
            
            if ($parentInfo instanceof FileInfo) {
                $parentInfo = $parentInfo->getDirectory();
            } else {
                $parentInfo = $parentInfo->getParent();
            }
        } while ($parentInfo != null &&
                 strcasecmp($parentInfo->getFullName(), $endDirPath) != 0);
    }

    public function getTimestamp($path, $searchOptions = self::WITHOUT_CONTENT)
    {
        return $this->getTimestampForInfo(Path::getInfo($path), $searchOptions);
    }

    public function setTimestamp($path, $timestamp = 0)
    {
        $this->setTimestampForInfo(Path::getInfo($path), $timestamp);
    }

    public function getTimestampForInfo(FileSystemInfo $info, $searchOptions = self::WITHOUT_CONTENT)
    {
        return $this->getTimestampHelper($info, $searchOptions);
    }

    public function setTimestampForInfo(FileSystemInfo $info, $timestamp = 0)
    {
        $this->setTimestampHelper($info, $timestamp === 0 ? time() : $timestamp);
    }
}