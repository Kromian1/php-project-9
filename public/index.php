<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Db\Connection;
use Analyzer\UrlValidator;
use Analyzer\CheckNormalizer;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use Analyzer\TimeNormalizer;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$conn = Connection::get();

$container = new Container();
$container->set('renderer', function () {
    $renderer = new PhpRenderer(__DIR__ . '/../templates');
    $renderer->setLayout('layout.phtml');
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
$container->set('guzzle', function () {
    return new Client([
        'timeout' => 10,
        'connect_timeout' => 5
        ]);
});
$container->set('TimeNormalizer', function () {
    return new TimeNormalizer();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();


$app->get('/', function (Request $request, Response $response) use ($container, $router) {
    $messages = $container->get('flash')->getMessages();
    $params = [
        'flash' => $messages,
        'title' => 'Анализатор страниц',
        'router' => $router
    ];
    return $container->get('renderer')->render($response, 'index.phtml', $params);
})->setName('home');


$app->post('/urls', function (Request $request, Response $response) use ($container, $conn, $router) {
    $data = $request->getParsedBody();
    $url = $data['url'] ?? '';

    $error = $container->get('urlValidator')->validateUrl($url);

    if ($error) {
        $container->get('flash')->addMessage('error', $error);
        return $response->withHeader('Location', $router->urlFor('home'))->withStatus(302);
    }

    $normalizedUrl = $container->get('urlValidator')->normalizeUrl($url);

    $checkSql = "SELECT id FROM urls WHERE name = :name";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':name', $normalizedUrl);
    $stmt->execute();
    $resultCheck = $stmt->fetch();

    if ($resultCheck) {
        $container->get('flash')->addMessage('warning', 'Страница уже существует');
        return $response->withHeader('Location', $router->urlFor('home'))->withStatus(302);
    }

    $sql = "INSERT INTO urls (name) VALUES (:url)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':url', $normalizedUrl);
    $stmt->execute();

    $newId = $conn->lastInsertId();
    $container->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $newId]))->withStatus(302);
})->setName('urls.post');


$app->get('/urls', function (Request $request, Response $response) use ($container, $conn) {
    $sql = "
        SELECT u.id, u.name, u.created_at, 
       (SELECT status_code FROM url_checks WHERE url_id = u.id ORDER BY created_at DESC LIMIT 1) as status_code 
        FROM urls u ORDER BY created_at DESC
        ";
    $stmt = $conn->query($sql);
    $urls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $normalizedTimeUrls = $container->get('TimeNormalizer')->normalizeTime($urls);

    $params = [
        'urls' => $normalizedTimeUrls,
        'title' => 'Сайты'
    ];

    return $container->get('renderer')->render($response, 'urls/urls.phtml', $params);
})->setName('urls.get');


$app->get('/urls/{id}', function (Request $request, Response $response, $args) use ($container, $conn, $router) {
    $id = $args['id'];

    $sql = "SELECT * FROM urls WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $url = $stmt->fetch(PDO::FETCH_ASSOC);

    $normalizedTimeUrl = $container->get('TimeNormalizer')->normalizeTime($url);

    if (!$normalizedTimeUrl) {
        return $response->withStatus(404);
    }
    $messages = $container->get('flash')->getMessages();

    //здесь получение данных по проверкам
    $sql = "SELECT * FROM url_checks WHERE url_id = :id ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $resultChecks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $normalizedChecks = $container->get('checkNormalizer')->normalizeChecks($resultChecks);

    $params = [
        'url' => $normalizedTimeUrl,
        'title' => 'Сайт',
        'checks' => $normalizedChecks,
        'flash' => $messages,
        'router' => $router
    ];

    return $container->get('renderer')->render($response, 'urls/url.phtml', $params);
})->setName('url.get');

$app->post(
    '/urls/{id}/checks',
    function (Request $request, Response $response, $args) use ($container, $conn, $router) {
    //запрос к нужному ресурсу, затем результат вносим в бд и отправляем в шаблон.
    //так как сейчас запрос не делается, просто добавляем пустую запись в бд
        $id = $args['id'];

        $sql = "SELECT name FROM urls WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $url = $stmt->fetchColumn();

        $h1 = '';
        $title = '';
        $description = '';
        //делаем запрос на проверяемый сайт
        try {
            $guzzleResponse = $container->get('guzzle')->request('GET', $url);
            $statusCode = $guzzleResponse->getStatusCode();
            //парсинг body сайта
            $body = (string) $guzzleResponse->getBody();
            $crawler = new Crawler($body);
            $h1 = $crawler->filter('h1')->count() ? $crawler->filter('h1')->text() : '';
            $title = $crawler->filter('title')->count() ? $crawler->filter('title')->text() : '';
            $description = $crawler->filter('meta[name="description"]')->count()
                ? $crawler->filter('meta[name="description"]')->attr('content')
                : '';
        } catch (ConnectException $e) {
            $statusCode = 500;
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()->getStatusCode() ?? 0;
        }

        $sql = "
    INSERT INTO url_checks (url_id, status_code, h1, title, description) 
    VALUES (:url_id, :status_code, :h1, :title, :description)
";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':url_id', $id);
        $stmt->bindParam(':status_code', $statusCode);
        $stmt->bindParam(':h1', $h1);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->execute();

        if ($statusCode == 200) {
            $container->get('flash')->addMessage('success', 'Страница успешно проверена');
            return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $id]))->withStatus(302);
        } else {
            $container->get('flash')->addMessage('error', 'Произошла ошибка при проверке, не удалось подключиться');
            return $response->withHeader('Location', $router->urlFor('url.get', ['id' => $id]))->withStatus(302);
        }
    }
)->setName('url.check.post');

$app->run();
