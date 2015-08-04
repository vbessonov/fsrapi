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

namespace VBessonov\FSRAPI\Controllers;

use \Silex\Application;
use \Silex\ControllerProviderInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \VBessonov\FSRAPI\Utils\Path;

/**
 * Description of FilesController
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class FilesController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get(
            '/',
            'VBessonov\FSRAPI\Controllers\FilesController::getRootFile'
        );
        $factory->get(
            '/{path}',
            'VBessonov\FSRAPI\Controllers\FilesController::getFile'
        );
        $factory->delete(
            '/{path}',
            'VBessonov\FSRAPI\Controllers\FilesController::deleteFile'
        );
        $factory->put(
            '/{path}',
            'VBessonov\FSRAPI\Controllers\FilesController::putFile'
        );
        $factory->post(
            '/{path}',
            'VBessonov\FSRAPI\Controllers\FilesController::postFile'
        );
//        $factory->match(
//            '/{path}',
//            'VBessonov\FSRAPI\Controllers\FilesController::headFile'
//        )->method('HEAD');

        return $factory;
    }

    public function getFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path);

        if (empty($realPath)) {
            return new Response("Path $path does not exist.", Response::HTTP_NOT_FOUND);
        }

        return $app->sendFile($realPath);
    }

    public function getRootFile(Application $app, Request $request)
    {
        return $this->getFile($app, $request, '/');
    }

    public function deleteFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path);

        if (empty($realPath)) {
            return new Response("Path $path does not exist", Response::HTTP_NOT_FOUND);
        }

        if (!empty($realPath) || $realPath != ROOT_DIR) {
            if (is_dir($realPath)) {
                rmdir($realPath);
            } else {
                unlink($realPath);
            }
        }

        return new Response("File successfully deleted");
    }

    public function putFile(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path);
        $content = $request->getContent(true);

        if (is_resource($content)) {
            $resourceType = get_resource_type($content);
        }

        $newFile = fopen(Path::combine(ROOT_DIR, 'newfile'), 'w');

        stream_copy_to_stream($content, $newFile);

        fclose($newFile);
        
        return new Response();
    }

    public function postFile(Application $app, Request $request, $path)
    {
        return $this->putFile($app, $request, $path);
    }

//    public function headFile(Application $app, Request $request, $path)
//    {
//        return new Response();
//    }
}