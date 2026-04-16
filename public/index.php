<?php

use Analyzer\Analyzer\HtmlParser;
use Analyzer\Analyzer\HttpClient;
use Analyzer\Analyzer\UrlValidator;
use Analyzer\Normalizer\CheckNormalizer;
use Analyzer\Normalizer\TimeNormalizer;
use Analyzer\Db\Connection;
use Analyzer\Db\UrlRepository;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;

require_once __DIR__ . '/../vendor/autoload.php';

session_start();

$conn = Connection::get();
$urlRepository = new UrlRepository($conn);

$container = new Container();
$container->set('renderer', function () use ($container) {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');
    $renderer->addAttribute('flash', $container->get('flash')->getMessages());
    return $renderer;
});
$container->set('flash', function () {
    return new Messages();
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
$errorMiddleware = $app->addErrorMiddleware(false, true, true);

$router = $app->getRouteCollector()->getRouteParser();
$renderer = $container->get('renderer');
$renderer->addAttribute('router', $router);

$container->set('router', function () use ($router) {
    return $router;
});

$app->get('/', function (Request $request, Response $response) use ($container) {
    $params = [
        'title' => 'Анализатор страниц'
    ];
    return $container->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');


$app->post('/urls', function (Request $request, Response $response) use ($container, $urlRepository) {
    $parsedBody = $request->getParsedBody();
    $data = is_array($parsedBody) ? $parsedBody : (array) $parsedBody;
    $url = $data['url'] ?? '';
    //собираем ошибки
    $error = $container->get('urlValidator')->validateUrl($url);

    if ($error) {
        $container->get('flash')->addMessageNow('error', $error);
        $params = [
            'title' => 'Анализатор страниц',
            'flash' => $container->get('flash')->getMessages()
        ];
        return $container->get('renderer')->render($response, 'index.phtml', $params)->withStatus(422);
    }

    $normalizedUrl = $container->get('urlValidator')->normalizeUrl($url);

    //здесь проверяем существует ли в БД сайт
    $existingId = $urlRepository->getId($normalizedUrl);

    if ($existingId) {
        $container->get('flash')->addMessage('warning', 'Страница уже существует');
        return $response
            ->withHeader('Location', $container
                ->get('router')->urlFor('url.get', ['id' => $existingId]))
            ->withStatus(302);
    }
    //если сайт не существует в БД, то добавляем его в БД
    $newId = $urlRepository->addUrl($normalizedUrl);
    $container->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response
        ->withHeader('Location', $container
            ->get('router')->urlFor('url.get', ['id' => $newId]))
        ->withStatus(302);
})->setName('urls.post');


$app->get('/urls', function (Request $request, Response $response) use ($container, $urlRepository) {
    //получаем список сайтов из БД с последним состоянием (код ответа)
    $urls = $urlRepository->getAllUrls();
    $normalizedTimeUrls = $container->get('TimeNormalizer')->normalizeTime($urls);

    $params = [
        'urls' => $normalizedTimeUrls,
        'title' => 'Сайты'
    ];

    return $container->get('renderer')->render($response, 'urls/index.phtml', $params);
})->setName('urls.get');


$app->get('/urls/{id:\d+}', function (Request $request, Response $response, $args) use ($container, $urlRepository) {
    $id = $args['id'];
    //получение url
    $url = $urlRepository->getUrl($id);
    //если url не существует, возвращаем 404
    if (!$url) {
        $params = [
            'title' => 'Ошибка 404'
        ];
        return $container->get('renderer')->render($response->withStatus(404), '404.phtml', $params);
    }
    $normalizedTimeUrl = $container->get('TimeNormalizer')->normalizeTime($url);

    if (!$normalizedTimeUrl) {
        return $response->withStatus(404);
    }

    //получаем данные по проверкам url
    $resultChecks = $urlRepository->getChecks($id);
    $normalizedChecks = $container->get('TimeNormalizer')->normalizeTime($resultChecks, 'Y-m-d H:i');

    $params = [
        'url' => $normalizedTimeUrl,
        'title' => 'Сайт',
        'checks' => $normalizedChecks
    ];

    return $container->get('renderer')->render($response, 'urls/show.phtml', $params);
})->setName('url.get');

$app->post(
    '/urls/{id:\d+}/checks',
    function (Request $request, Response $response, $args) use ($container, $urlRepository) {
        $id = $args['id'];
        //получаем url, для проверки ресурса
        $url = $urlRepository->getUrlName($id);

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
            $urlRepository->updateChecks($id, $statusCode, $normalizedBody);
            return $response
                ->withHeader('Location', $container->get('router')
                    ->urlFor('url.get', ['id' => $id]))
                ->withStatus(302);
        } else {
            $container->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response
                ->withHeader('Location', $container->get('router')
                    ->urlFor('url.get', ['id' => $id]))
                ->withStatus(302);
        }
    }
)->setName('url.check.post');

$app->any('/{slug:.+}', function (Request $request, Response $response) use ($container) {
    $params = [
        'title' => 'Ошибка 404'
    ];
    return $container->get('renderer')->render($response->withStatus(404), '404.phtml', $params);
});

$app->run();
