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

namespace VBessonov\FSRAPI\Cache;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Description of CacheServiceProvider
 *
 * @author Vyacheslav Bessonov <v.bessonov@hotmail.com>
 */
class FileSystemCacheServiceProvider implements ServiceProviderInterface
{
    public function boot(Application $app)
    {
        
    }

    public function register(Application $app)
    {
        $app['fsrapi.cache'] = $app->share(
            function () use ($app) {
                try {
                    $cache = $app['caches']['redis'];
                } catch (\Exception $ex) {
                    $app['monolog']->addError("Redis is not available: {$ex->getMessage()})");
                }

                if (empty($cache)) {
                    $cache = $app['caches']['file'];
                }

                return new FileSystemCache($cache);
            }
        );
    }
}