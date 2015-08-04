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
class MetadataController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get(
            '/',
            'VBessonov\FSRAPI\Controllers\MetadataController::getRootMetadata'
        );
        $factory->get(
            '/{path}',
            'VBessonov\FSRAPI\Controllers\MetadataController::getMetadata'
        );

        return $factory;
    }

    public function getMetadata(Application $app, Request $request, $path)
    {
        $realPath = Path::getRealPath($path);

        if ($realPath === false) {
            return new Response("Path $path does not exist.", Response::HTTP_NOT_FOUND);
        }

        $metadata = $this->getFileMetadata($realPath);
        $contents = $request->get('contents');

        if (is_dir($realPath) && !empty($contents)) {
            $files = array_diff(scandir($realPath), array('..', '.'));
            $filesMetadata = array();

            foreach ($files as $file) {
                $fileMetadata = $this->getFileMetadata(Path::combine($realPath, $file));
                array_push($filesMetadata, $fileMetadata);
            }

            $metadata['contents'] = $filesMetadata;
        }

        return $app->json($metadata);
    }

    public function getRootMetadata(Application $app, Request $request)
    {
        return $this->getMetadata($app, $request, '/');
    }

    private function getFileMetadata($path)
    {
        $virtualPath = Path::getVirtualPath($path);

        $metadata = array(
            'size' => filesize($path),
            'modified' => strftime('%a, %d %b %Y %H:%M:%S %z', filectime($path)),
            'is_dir' => is_dir($path),
            'path' => urldecode($virtualPath)
        );

        return $metadata;
    }
}