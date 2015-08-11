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

use VBessonov\FSRAPI\Tests\BaseTest;

/**
 * Description of FileSystemInfoTest
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
abstract class FileSystemInfoTest extends BaseTest
{
    const TEST_DIRECTORY = __DIR__ . '/../../../fs';

    protected abstract function getTestPath();
    
    protected abstract function getInfo();

    protected abstract function getSpecificInfo($path);

    public function testGetName()
    {
        $result = $this->getInfo()->getName();
        $expected = basename($this->getTestPath());
        $this->assertEquals($expected, $result);
    }

    public function testGetFullName()
    {
        $result = $this->getInfo()->getFullName();
        $expected = $this->getTestPath();
        $this->assertEquals($expected, $result);
    }

    public function testGetLastWriteTime()
    {
        $result = $this->getInfo()->getLastWriteTime();
        $expected = filemtime($this->getTestPath());
        $this->assertEquals($expected, $result);
    }

    public function testGetLastAccessTime()
    {
        $result = $this->getInfo()->getLastAccessTime();
        $expected = fileatime($this->getTestPath());
        $this->assertEquals($expected, $result);
    }

    public function testExists()
    {
        $result = $this->getInfo()->exists();
        $expected = file_exists($this->getTestPath());
        $this->assertEquals($expected, $result);

        $path = $this->getTestPath() . '12345';
        $result = $this->getSpecificInfo($path)->exists();
        $expected = file_exists($path);
        $this->assertEquals($expected, $result);
    }
}