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

namespace VBessonov\FSRAPI\Tests\IO;

use VBessonov\FSRAPI\IO\Path;
use VBessonov\FSRAPI\Tests\BaseTest;

/**
 * Description of PathTest
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class PathTest extends BaseTest
{
    public function testCombine()
    {
        $path1 = '';
        $path2 = '';
        $result = Path::combine($path1, $path2);
        $expected = '';
        $this->assertEquals($expected, $result);

        $path1 = '/';
        $path2 = '';
        $result = Path::combine($path1, $path2);
        $expected = $path1;
        $this->assertEquals($expected, $result);

        $path1 = '/private/var/temp';
        $path2 = '';
        $result = Path::combine($path1, $path2);
        $expected = $path1;
        $this->assertEquals($expected, $result);

        $path1 = '';
        $path2 = '/';
        $result = Path::combine($path1, $path2);
        $expected = $path2;
        $this->assertEquals($expected, $result);

        $path1 = '/';
        $path2 = '/';
        $result = Path::combine($path1, $path2);
        $expected = DIRECTORY_SEPARATOR;
        $this->assertEquals($expected, $result);

        $path1 = '';
        $path2 = '/private/var/temp';
        $result = Path::combine($path1, $path2);
        $expected = $path2;
        $this->assertEquals($expected, $result);

        $path1 = '/private/var/temp';
        $path2 = '';
        $result = Path::combine($path1, $path2);
        $expected = $path1;
        $this->assertEquals($expected, $result);

        $path1 = '/private';
        $path2 = 'var/temp';
        $result = Path::combine($path1, $path2);
        $expected = '/private/var/temp';
        $this->assertEquals($expected, $result);

        $path1 = '/private/';
        $path2 = 'var/temp';
        $result = Path::combine($path1, $path2);
        $expected = '/private/var/temp';
        $this->assertEquals($expected, $result);

        $path1 = '/private';
        $path2 = '/var/temp';
        $result = Path::combine($path1, $path2);
        $expected = '/private/var/temp';
        $this->assertEquals($expected, $result);

        $path1 = '/private/';
        $path2 = '/var/temp';
        $result = Path::combine($path1, $path2);
        $expected = '/private/var/temp';
        $this->assertEquals($expected, $result);
    }

    public function testGetRealPath()
    {
        $virtualPath = '/';
        $result = Path::getRealPath($virtualPath);
        $expected = $virtualPath;
        $this->assertEquals($expected, $result);

        $virtualPath = '/files';
        $result = Path::getRealPath($virtualPath);
        $expected = $virtualPath;
        $this->assertEquals($expected, $result);

        $rootDir = '/';
        $virtualPath = '/';
        $result = Path::getRealPath($virtualPath, $rootDir);
        $expected = $rootDir;
        $this->assertEquals($expected, $result);

        $rootDir = '/';
        $virtualPath = '/files';
        $result = Path::getRealPath($virtualPath, $rootDir);
        $expected = '/files';
        $this->assertEquals($expected, $result);

        $rootDir = __DIR__;
        $virtualPath = '/';
        $result = Path::getRealPath($virtualPath, $rootDir);
        $expected = $rootDir . '/';
        $this->assertEquals($expected, $result);

        $rootDir = __DIR__ . '/';
        $virtualPath = '/files';
        $result = Path::getRealPath($virtualPath, $rootDir);
        $expected = $rootDir . 'files';
        $this->assertEquals($expected, $result);
    }

    public function testGetVirtualPath()
    {
        $realPath = '/';
        $result = Path::getVirtualPath($realPath);
        $expected = $realPath;
        $this->assertEquals($expected, $result);

        $realPath = '/files';
        $result = Path::getVirtualPath($realPath);
        $expected = $realPath;
        $this->assertEquals($expected, $result);

        $rootDir = '/';
        $realPath = '/';
        $result = Path::getVirtualPath($realPath, $rootDir);
        $expected = $realPath;
        $this->assertEquals($expected, $result);

        $rootDir = '/';
        $realPath = '/files';
        $result = Path::getVirtualPath($realPath, $rootDir);
        $expected = $realPath;
        $this->assertEquals($expected, $result);

        $rootDir = __DIR__;
        $realPath = __DIR__ . '/files';
        $result = Path::getVirtualPath($realPath, $rootDir);
        $expected = '/files';
        $this->assertEquals($expected, $result);

        $rootDir = __DIR__;
        $realPath = __DIR__ . '/../files';
        $result = Path::getVirtualPath($realPath, $rootDir);
        $expected = '/../files';
        $this->assertEquals($expected, $result);
    }

    public function testExists()
    {
        $this->assertTrue(Path::exists(__FILE__));
        $this->assertFalse(Path::exists(__FILE__ . '12345'));
    }

    public function testIsDir()
    {
        $this->assertTrue(Path::isDir(__DIR__));
        $this->assertFalse(Path::isDir(__FILE__));
    }
}