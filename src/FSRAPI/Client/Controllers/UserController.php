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

namespace VBessonov\FSRAPI\Client\Controllers;

use ReflectionClass;
use Silex\Application;
use Silex\ControllerProviderInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use VBessonov\FSRAPI\IO\Path;

/**
 * Description of ClientController
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class UserController implements ControllerProviderInterface
{
    private function createRequest(Application $app, $url, $method, $parameters = array(), $content = null)
    {
        $request = Request::create(
            $url,
            $method,
            $parameters,
            array(),
            array(),
            array(),
            $content
        );
        $userToken = $app['security.token_storage']->getToken();
        $user = $userToken->getUser();
        $wsseHeader = sprintf(
            'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
            $user->getUsername(),
            $user->getPassword(),
            uniqid(),
            time()
        );
        
        $request->headers->set('x-wsse', $wsseHeader);

        if ($app['debug']) {
            $request->query->set('XDEBUG_SESSION_START', 'netbeans-xdebug');
        }

        return $request;
    }

    private function createDirectory(Application $app, $path)
    {
        $path = ltrim($path, '/');
        $url = "http://fsrapi/api/files/$path?is_dir=true";
        $request = $this->createRequest(
            $app,
            $url,
            Request::METHOD_POST
        );

        return array($request, $app->handle($request));
    }

    private function createFile(Application $app, $path, UploadedFile $file)
    {
        $path = Path::combine(ltrim($path, '/'), $file->getClientOriginalName());
        $url = "http://fsrapi/api/files/$path";
        $contents = $this->getFileContents($file);
        $request = $this->createRequest(
            $app,
            $url,
            Request::METHOD_POST,
            array(),
            $contents
        );
        
        return array($request, $app->handle($request));
    }
    
    private function getDirectoryInfo(Application $app, $path)
    {
        $path = ltrim($path, '/');
        $url = "http://fsrapi/api/metadata/$path";
        $request = $this->createRequest(
            $app,
            $url,
            Request::METHOD_GET,
            array('contents' => true)
        );

        return $app['http_cache']->handle($request);
    }

    private function getFile(Application $app, $path)
    {
        $path = ltrim($path, '/');
        $url = "http://fsrapi/api/files/$path";
        $request = $this->createRequest(
            $app,
            $url,
            Request::METHOD_GET
        );

        return $app['http_cache']->handle($request);
    }

    private function deleteFile(Application $app, $path)
    {
        $path = ltrim($path, '/');
        $url = "http://fsrapi/api/files/$path";
        $request = $this->createRequest(
            $app,
            $url,
            Request::METHOD_DELETE
        );

        return array($request, $app->handle($request));
    }

    private function getFileContents(SplFileInfo $file)
    {
        $fileObject = $file->openFile();
        $size = $fileObject->fstat()['size'];
        $contents = $fileObject->fread($size);
        $fileObject = null;
        
        return $contents;
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get(
            '/{path}',
            'VBessonov\FSRAPI\Client\Controllers\UserController::index'
        )->assert('path', '.*');
        $factory->post(
            '/{path}',
            'VBessonov\FSRAPI\Client\Controllers\UserController::postIndex'
        )->assert('path', '.*');

        return $factory;
    }

    public function index(Application $app, Request $request, $path)
    {
        $twigParameters = array();
        
        if ($request->query->getBoolean('is_file')) {
            $response = $this->getFile($app, $path);

            if ($response instanceof BinaryFileResponse) {
                $contents = $this->getFileContents($response->getFile());

                $out = fopen('php://output', 'wb');

                try {
                    fwrite($out, $contents);
                } finally {
                    fclose($out);
                }
            }
        } else {
            $response = $this->getDirectoryInfo($app, $path);
            $contents = $response->getContent();
            $json = json_decode($contents);
            $twigParameters['directory'] = $json;
        }
        
        $responseInfo = $app['session']->get('response');
        $cacheInfo = $response->headers->get('X-Symfony-Cache');
        $rateMaxLimitInfo = $response->headers->get('X-RateLimit-Limit');
        $rateRemainingLimit = $response->headers->get('X-RateLimit-Remaining');

        if (null !== $responseInfo) {
            $twigParameters['response_info'] = $responseInfo;
            $app['session']->set('response', null);
        }
        if (null !== $cacheInfo) {
            $twigParameters['cache_info'] = $cacheInfo;
        }
        if (null !== $rateMaxLimitInfo &&
            null !== $rateRemainingLimit) {
            $twigParameters['rate_limit_info'] = array(
                'limit' => $rateMaxLimitInfo,
                'remaining' => $rateRemainingLimit
            );
        }
        $twigParameters['uri'] = $request->getUri();
        
        return $app['twig']->render(
            'user.twig',
            $twigParameters
        );
    }

    public function postIndex(Application $app, Request $request, $path)
    {
        $subRequest = null;
        $response = null;
        
        if ($request->request->has('createDirectory') &&
            $request->request->has('directoryName')) {
            $directoryName = $request->request->get('directoryName');
            list($subRequest, $response) = $this->createDirectory($app, Path::combine($path, $directoryName));
        } else if ($request->request->get('createFile') &&
                   $request->files->count() > 0) {
            $files = $request->files->all();
            $file = $files['userfile'];
            list($subRequest, $response) = $this->createFile($app, $path, $file);
        } else if ($request->request->count() > 0) {
            foreach ($request->request->keys() as $key) {
                $matches = array();
                
                if (preg_match('/^delete_(.+)$/', $key, $matches)) {
                    $fileName = str_replace('#', '.', $matches[1]);
                    
                    list($subRequest, $response) = $this->deleteFile($app, $fileName);
                    break;
                }
            }
        }

        if (null !== $response) {
            $reflectionClass = new ReflectionClass(get_class($response));
            $statusTextProperty = $reflectionClass->getProperty('statusText');

            $statusTextProperty->setAccessible(true);

            $statusCode = $response->getStatusCode();
            $statusText = $statusTextProperty->getValue($response);
            
            $app['session']->set(
                'response',
                array(
                    'method' => $subRequest->getMethod(),
                    'uri' => $subRequest->getUri(),
                    'status_code' => $statusCode,
                    'status_text' => $statusText
                )
            );
        }
            
        $url = $request->getUriForPath('/');
//        $subRequest = Request::create(
//            $url,
//            'GET',
//            array(),
//            $request->cookies->all(),
//            array(), $request->server->all()
//        );
        
//        return $app->handle($subRequest, HttpKernelInterface::MASTER_REQUEST);
        return $app->redirect($url);
//        return new \Symfony\Component\HttpFoundation\Response();
    }
}