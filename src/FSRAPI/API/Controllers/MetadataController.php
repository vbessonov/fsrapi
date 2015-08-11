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

namespace VBessonov\FSRAPI\API\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VBessonov\FSRAPI\Config;
use VBessonov\FSRAPI\IO\FileSystemInfoSerializer;
use VBessonov\FSRAPI\IO\Path;

/**
 * Description of FilesController
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class MetadataController implements ControllerProviderInterface
{
    use FileSystemTrait;
    
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get(
            '/{path}',
            'VBessonov\FSRAPI\API\Controllers\MetadataController::getMetadata'
        )->assert('path', '.*');

        return $factory;
    }

    public function getMetadata(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path, Config::getRootDir());

        if (!Path::exists($realPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
        if ($request->getMethod() === 'HEAD') {
            return new Response('', Response::HTTP_OK);
        }

        $lastWriteTime = $response = $this->getLastWriteTime($app, $realPath);
        
        if (($response = $this->hasCachedResponse($request, $lastWriteTime)) !== false) {
            return $response;
        }

        $searchOptions =
            $request->query->getBoolean('contents')
            ? FileSystemInfoSerializer::TOP_DIRECTORY_ONLY
            : FileSystemInfoSerializer::WITHOUT_CONTENT;
        $info = Path::getInfo($realPath);
        $json = FileSystemInfoSerializer::toJson($info, $searchOptions);

        $response = $app->json($json);
        $response->setLastModified($lastWriteTime);
        $response->setPublic();

        return $response;
    }
}