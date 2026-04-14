<?php

use Analyzer\HttpClient;
use Analyzer\HtmlParser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Db\Connection;
use Analyzer\UrlValidator;
use Analyzer\CheckNormalizer;
use Analyzer\TimeNormalizer;
use Db\UrlRepository;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$conn = Connection::get();
$urlRepo = new UrlRepository($conn);

$container = new Container();
$container->set('renderer', function () use ($container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');
    $renderer->addAttribute('flash', $container->get('flash')->getMessages());
    return $renderer;
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$container->set('urlValidator', function () {
    return new UrlValidator();
});
$container->set('checkNormalizer', function () {
    return new CheckNormalizer();
});
$container->set('HttpClient', function () {
    return new HttpClient();
});
$container->set('TimeNormalizer', function () {
    return new TimeNormalizer();
});
$container->set('HtmlParser', function () {
    return new HtmlParser();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();


$app->get('/', function (Request $request, Response $response) use ($container, $router) {
    $params = [
        'title' => 'Анализатор страниц',
        'router' => $router
    ];
    return $container->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');


$app->post('/urls', function (Request $request, Response $response) use ($container, $urlRepo, $router) {
    $data = $request->getParsedBody();
    $url = $data['url'] ?? '';
    //собираем ошибки
    $error = $container->get('urlValidator')->validateUrl($url);

    if ($error) {
        $container->get('flash')->addMessageNow('error', $error);
        $params = [
            'title' => 'Анализатор страниц',
            'router' => $router
        ];
        return $container->get('renderer')->render($response, 'index.phtml', $params)->withStatus(422);
    }

    $normalizedUrl = $container->get('urlValidator')->normalizeUrl($url);

    //здесь проверяем существует ли в БД сайт
    $existingId = $urlRepo->getId($normalizedUrl);

    if ($existingId) {
        $container->get('flash')->addMessage('warning', 'Страница уже существует');
        return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $existingId]))->withStatus(302);
    }
    //если сайт не существует в БД, то добавляем его в БД
    $newId = $urlRepo->addUrl($normalizedUrl);
    $container->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $newId]))->withStatus(302);
})->setName('urls.post');


$app->get('/urls', function (Request $request, Response $response) use ($container, $urlRepo) {
    //получаем список сайтов из БД с последним состоянием (код ответа)
    $urls = $urlRepo->getAllUrls();
    $normalizedTimeUrls = $container->get('TimeNormalizer')->normalizeTime($urls);

    $params = [
        'urls' => $normalizedTimeUrls,
        'title' => 'Сайты'
    ];

    return $container->get('renderer')->render($response, 'urls/urls.phtml', $params);
})->setName('urls.get');


$app->get('/urls/{id}', function (Request $request, Response $response, $args) use ($container, $urlRepo, $router) {
    $id = $args['id'];
    //получение url
    $url = $urlRepo->getUrl($id);
    $normalizedTimeUrl = $container->get('TimeNormalizer')->normalizeTime($url);

    if (!$normalizedTimeUrl) {
        return $response->withStatus(404);
    }

    //получаем данные по проверкам url
    $resultChecks = $urlRepo->getChecks($id);
    $normalizedChecks = $container->get('TimeNormalizer')->normalizeTime($resultChecks, 'Y-m-d H:i');

    $params = [
        'url' => $normalizedTimeUrl,
        'title' => 'Сайт',
        'checks' => $normalizedChecks,
        'router' => $router
    ];

    return $container->get('renderer')->render($response, 'urls/url.phtml', $params);
})->setName('url.get');

$app->post(
    '/urls/{id}/checks',
    function (Request $request, Response $response, $args) use ($container, $urlRepo, $router) {
        $id = $args['id'];
        //получаем url, для проверки ресурса
        $url = $urlRepo->getUrlName($id);

        //делаем запрос на проверяемый сайт
        $answer = $container->get('HttpClient')->fetch('GET', $url);
        $statusCode = $answer['statusCode'];
        $body = $answer['body'] ?? '';

        if ($statusCode == 200) {
            $container->get('flash')->addMessage('success', 'Страница успешно проверена');
            //делаем парсинг
            $parsedBody = $container->get('HtmlParser')->parse($body);
            $normalizedBody = $container->get('checkNormalizer')->normalizeCheckBody($parsedBody);
            //вставляем результат запроса к ресурсу в БД
            $urlRepo->updateChecks($id, $statusCode, $normalizedBody);
            return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $id]))->withStatus(302);
        } else {
            $container->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $id]))->withStatus(302);
        }
    }
)->setName('url.check.post');

$app->run();
