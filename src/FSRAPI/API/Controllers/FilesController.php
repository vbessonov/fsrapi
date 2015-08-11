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
use VBessonov\FSRAPI\IO\DirectoryInfo;
use VBessonov\FSRAPI\IO\Path;

/**
 * Description of FilesController
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class FilesController implements ControllerProviderInterface
{
    use FileSystemTrait;
    use ResponseTrait;
    
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get(
            '/{path}',
            'VBessonov\FSRAPI\API\Controllers\FilesController::getFile'
        )->assert('path', '.*');
        $factory->delete(
            '/{path}',
            'VBessonov\FSRAPI\API\Controllers\FilesController::deleteFile'
        )->assert('path', '.*');
        $factory->put(
            '/{path}',
            'VBessonov\FSRAPI\API\Controllers\FilesController::putFile'
        )->assert('path', '.*');
        $factory->post(
            '/{path}',
            'VBessonov\FSRAPI\API\Controllers\FilesController::postFile'
        )->assert('path', '.*');

        return $factory;
    }

    public function getFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path, Config::getRootDir());

        if (!Path::exists($realPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
        if ($request->getMethod() === 'HEAD') {
            return new Response('', Response::HTTP_OK);
        }
        if (Path::isDir($realPath)) {
            return $this->createResponse(Response::HTTP_BAD_REQUEST, "'$path' is a directory");
        }

        $lastWriteTime = $this->getLastWriteTime($app, $realPath);

        if (($response = $this->hasCachedResponse($request, $lastWriteTime)) !== false) {
            return $response;
        }

        $response = $app->sendFile($realPath);
        $response->setLastModified($lastWriteTime);

        return $response;
    }

    public function deleteFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path, Config::getRootDir());

        if (!Path::exists($realPath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }
        if (strcasecmp($realPath, Config::getRootDir()) == 0) {
            return $this->createResponse(Response::HTTP_BAD_REQUEST, "Cannot delete root directory");
        }

        if (Path::isDir($realPath)) {
            $dirInfo = new DirectoryInfo($realPath);

            if (count($dirInfo->getFileSystemInfos()) > 0) {
                return $this->createResponse(Response::HTTP_BAD_REQUEST, "Directory '$path' is not empty");
            }
            
            rmdir($realPath);
        } else {
            unlink($realPath);
        }

        $info = Path::getInfo($realPath);
        $app['fsrapi.cache']->setTimestampForInfo($info);

        return new Response();
    }

    public function putFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path, Config::getRootDir());

        if (Path::exists($realPath) &&
            Path::isDir($realPath)) {
            return $this->createResponse(Response::HTTP_BAD_REQUEST, "Directory '$path' already exists");
        }
        if ($request->query->getBoolean('is_dir') ||
            $request->request->getBoolean('is_dir')) {
            if (Path::exists($realPath)) {
                return $this->createResponse(Response::HTTP_BAD_REQUEST, "Directory '$path' already exists");
            }
            
            mkdir($realPath);
        } else {
           $content = $request->getContent(true);

            if (!is_resource($content)) {
                return $this->createResponse(Response::HTTP_BAD_REQUEST, "Request has incorrect body");
            }

            $resourceType = get_resource_type($content);

            if ($resourceType != 'stream') {
                return $this->createResponse(Response::HTTP_BAD_REQUEST, "Request has incorrect body");
            }

            $newFile = fopen($realPath, 'w');

            try {
                stream_copy_to_stream($content, $newFile);
            } finally {
                fclose($newFile);
            }
        }

        $info = Path::getInfo($realPath);
        $app['fsrapi.cache']->setTimestampForInfo($info, $info->getLastWriteTime());

        $response = new Response();
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }

    public function postFile(Application $app, Request $request, $path)
    {
        return $this->putFile($app, $request, $path);
    }
}