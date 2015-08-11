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

require_once __DIR__ . '/bootstrap.php';

use CHH\Silex\CacheServiceProvider;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\DBAL\Schema\Table;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use VBessonov\FSRAPI\API\Controllers\FilesController;
use VBessonov\FSRAPI\API\Controllers\MetadataController;
use VBessonov\FSRAPI\Cache\FileSystemCacheServiceProvider;
use VBessonov\FSRAPI\Client\Controllers\LoginController;
use VBessonov\FSRAPI\Client\Controllers\UserController;
use VBessonov\FSRAPI\RateLimit\RateLimitProvider;
use VBessonov\FSRAPI\Security\WsseListener;
use VBessonov\FSRAPI\Security\WsseProvider;
use VBessonov\FSRAPI\Users\UserProvider;

$app = new Application();
$app['debug'] = true;
$app['users'] = $app->share(
    function() use ($app) {
        return new UserProvider($app['db']);
    }
);
$app['redis'] = new Redis();
$app['redis']->connect('127.0.0.1', 6379);

//$app->register(new RateLimitProvider());
$app->register(
    new MonologServiceProvider(),
    array(
        'monolog.logfile' => __DIR__ . '/../logs/log'
    )
);
$app->register(
    new HttpCacheServiceProvider(),
    array(
        'http_cache.cache_dir' => __DIR__ . '/../files/cache/http_cache',
        'http_cache.esi' => null,
    )
);
$app->register(
    new CacheServiceProvider,
    array(
        'cache.options' => array(
            'default' => array('driver' => 'apc'),
            'file' => array(
                'driver' => 'filesystem',
                'directory' => __DIR__ . '/../files/cache/fs_cache'
            ),
            'redis' => array(
                'driver' => function () use ($app) {
                    $redisCache = new RedisCache();
                    $redisCache->setRedis($app['redis']);

                    return $redisCache;
                }
            )
        )
    )
);
$app->register(new FileSystemCacheServiceProvider());
$app->register(
    new DoctrineServiceProvider(),
    array(
        'db.options' => array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'fsrapi',
            'user' => 'admin',
            'password' => 'Qwerty1'
        )
    )
);
$app->register(new SessionServiceProvider());
$app['security.authentication_listener.factory.wsse'] = $app->protect(
    function ($name, $options) use ($app) {
        $app['security.authentication_provider.' . $name . '.wsse'] = $app->share(
            function () use ($app) {
                return new WsseProvider($app['users'], __DIR__ . '/../security_cache');
            }
        );
        $app['security.authentication_listener.' . $name . '.wsse'] = $app->share(
            function () use ($app) {
                return new WsseListener($app['security.token_storage'], $app['security.authentication_manager']);
            }
        );
        return array(
            'security.authentication_provider.' . $name . '.wsse',
            'security.authentication_listener.' . $name . '.wsse',
            null,
            'pre_auth'
        );
    }
);
$app->register(
    new SecurityServiceProvider(),
    array(
        'security.firewalls' => array(
            'user' => array(
                'pattern' => '^/user',
                'form' => array(
                    'login_path' => '/login',
                    'check_path' => '/user/login_check'
                ),
                'logout' => array(
                    'logout_path' => '/user/logout',
                    'invalidate_session' => true
                ),
                'users' => $app['users']
            ),
            'api' => array(
                'pattern' => '^/api/.*$',
                'wsse' => true,
                'stateless' => true,
                'users' => $app['users']
            ),
            'login' => array(
                'pattern' => '^/login$',
                'anonymous' => true
            ),
            'default' => array(
                'anonymous' => true,
            )
        )
    )
);
$schema = $app['db']->getSchemaManager();
if (!$schema->tablesExist('users')) {
    $users = new Table('users');
    $users->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
    $users->setPrimaryKey(array('id'));
    $users->addColumn('username', 'string', array('length' => 32));
    $users->addUniqueIndex(array('username'));
    $users->addColumn('password', 'string', array('length' => 255));
    $users->addColumn('roles', 'string', array('length' => 255));

    $schema->createTable($users);
    $encoder = $app['security.encoder.digest'];

    $app['db']->insert('users',
        array(
            'username' => 'user',
            'password' => $encoder->encodePassword('user'),
            'roles' => 'ROLE_USER'
        )
    );
}
$app->register(new UrlGeneratorServiceProvider());
$app->register(
    new TwigServiceProvider(),
    array(
        'twig.path' => __DIR__ . '/../views'
    )
);

$app->mount('/login', new LoginController());
$app->mount('/user', new UserController());
$app->mount('/api/files', new FilesController());
$app->mount('/api/metadata', new MetadataController());
$app->get(
    '/',
    function () use ($app) {
        return $app->redirect('/user');
    }
);


Request::setTrustedProxies(array('127.0.0.1'));

return $app;