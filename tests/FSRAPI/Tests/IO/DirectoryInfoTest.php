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

use VBessonov\FSRAPI\IO\DirectoryInfo;

/**
 * Description of DirectoryInfoTest
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class DirectoryInfoTest extends FileSystemInfoTest
{
    protected function getTestPath()
    {
        return self::TEST_DIRECTORY;
    }

    protected function getInfo()
    {
        return new DirectoryInfo($this->getTestPath());
    }

    protected function getSpecificInfo($path)
    {
        return new DirectoryInfo($path);
    }

    public function testGetFileSystemInfos()
    {
        
    }
}