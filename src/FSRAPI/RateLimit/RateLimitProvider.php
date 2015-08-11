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

namespace VBessonov\FSRAPI\RateLimit;

use Exception;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use VBessonov\FSRAPI\RateLimit\Storage\DoctrineRateLimitStorage;

/**
 * Description of SilexRateLimitProvider
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class RateLimitProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['app.ratelimit'] = $app->share(
            function () use ($app) {
                try {
                    $cache = $app['caches']['redis'];
                } catch (Exception $ex) {
                    $app['monolog']->addError("Redis is not available: {$ex->getMessage()})");
                }

                if (empty($cache)) {
                    $cache = $app['caches']['file'];
                }
                
                $storage = new DoctrineRateLimitStorage($cache);
                $rateLimiter = new RateLimiter($storage);

                return $rateLimiter;
            }
        );

        $app->before(
            function (Request $request) use ($app) {
                $token = $app['security.token_storage']->getToken();

                if (null !== $token) {
                    $userId = $token->getUsername();

                    if ($app['app.ratelimit']->hasReachedLimit($userId)) {
                        $response = new Response('', Response::HTTP_TOO_MANY_REQUESTS);
                        
                        $response->headers->add(
                            array(
                                'X-RateLimit-Limit' => $app['app.ratelimit']->getMaxLimit(),
                                'X-RateLimit-Remaining' => 0
                            )
                        );

                        return $response;
                    }
                }
            }
        );
        $app->after(
            function (Request $request, Response $response) use ($app) {
                if ($response->headers->has('X-RateLimit-Limit')) {
                    return;
                }

                $token = $app['security.token_storage']->getToken();
                if (null !== $token) {
                    $userId = $token->getUsername();

                    $app['app.ratelimit']->substract($userId, 1);
                    $currentAmount = $app['app.ratelimit']->getCurrentAmount($userId);

                    $response->headers->add(
                        array(
                            'X-RateLimit-Limit' => $app['app.ratelimit']->getMaxLimit(),
                            'X-RateLimit-Remaining' => $currentAmount
                        )
                    );
                }
            }
        );
    }

    public function boot(Application $app)
    {
        
    }
}