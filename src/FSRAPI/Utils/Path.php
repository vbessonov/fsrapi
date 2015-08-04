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

namespace VBessonov\FSRAPI\Utils;

/**
 * Description of Path
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class Path
{
    public static function getRealPath($path)
    {
        return realpath(ROOT_DIR . DIRECTORY_SEPARATOR . $path);
    }

    public static function getVirtualPath($path)
    {
        $virtualPath = str_replace(ROOT_DIR, '', $path);
        $dirname = dirname($virtualPath);

        if (empty($dirname) || $dirname === '.') {
            $virtualPath = '/' . $virtualPath;
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

        if ($path1[strlen($path1) - 1] !== DIRECTORY_SEPARATOR) {
            $completePath .= DIRECTORY_SEPARATOR;
        }
        if ($path2[strlen($path2) - 1] !== DIRECTORY_SEPARATOR) {
            $completePath .= $path2;
        } else {
            $completePath .= $path2[strlen($path2) - 1];
        }

        return $completePath;
    }
}