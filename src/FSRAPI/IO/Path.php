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
 * Description of Path
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class Path
{
    public static function getRealPath($virtualPath, $rootDir = DIRECTORY_SEPARATOR)
    {
        return self::combine($rootDir, $virtualPath);
    }

    public static function getVirtualPath($realPath, $rootDir = DIRECTORY_SEPARATOR)
    {
        $virtualPath = $realPath;

        if (DIRECTORY_SEPARATOR !== $rootDir) {
            $rootDirPos = stripos($realPath, $rootDir);

            if ($rootDirPos === 0) {
                $virtualPath = substr($realPath, strlen($rootDir));
            }
        }

        $dirname = dirname($virtualPath);

        if (empty($dirname) || $dirname === '.') {
            $virtualPath = DIRECTORY_SEPARATOR . $virtualPath;
        }

        return $virtualPath;
    }

    public static function combine($path1, $path2)
    {
        if (strlen($path2) == 0) {
            return $path1;
        }
        if (strlen($path1) == 0) {
            return $path2;
        }
        
        $completePath = $path1;

        if ($completePath[strlen($completePath) - 1] !== DIRECTORY_SEPARATOR) {
            $completePath .= DIRECTORY_SEPARATOR;
        }
        if ($path2[0] !== DIRECTORY_SEPARATOR) {
            $completePath .= $path2;
        } else {
            $completePath .= substr($path2, 1);
        }

        return $completePath;
    }

    public static function exists($path)
    {
        return file_exists($path);
    }

    public static function isDir($path)
    {
        return is_dir($path);
    }

    public static function getInfo($path)
    {
        if (self::isDir($path)) {
            return new DirectoryInfo($path);
        } else {
            return new FileInfo($path);
        }
    }
}