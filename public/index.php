<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Db\Connection;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$conn = Connection::get();

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function (Request $request, Response $response) use ($container) {
    $messages = $container->get('flash')->getMessages();
    $params = ['flash' => $messages];
    return $container->get('renderer')->render($response, 'index.phtml', $params);
});

$app->post('/urls', function (Request $request, Response $response) use ($container, $conn) {
    $data = $request->getParsedBody();
    $url = $data['url'] ?? '';

    $errors = [];
    if (empty($url)) {
        $errors[] = 'URL не должен быть пустым';
    }
    if (strlen($url) > 255) {
        $errors[] = 'URL превышает 255 символов';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Некорректный URL';
    }
    if (!empty($errors)) {
        foreach ($errors as $error) {
            $container->get('flash')->addMessage('error', $error);
        }
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    $parsedUrl = parse_url($url);
    $normalizedUrl = strtolower($parsedUrl['scheme'] . '://' . $parsedUrl['host']);

    $checkSql = "SELECT id FROM urls WHERE name = :name";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':name', $normalizedUrl);
    $stmt->execute();
    $resultCheck = $stmt->fetch();

    if ($resultCheck) {
        $container->get('flash')->addMessage('warning', 'Страница уже существует');
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    $sql = "INSERT INTO urls (name) VALUES (:url)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':url', $normalizedUrl);
    $stmt->execute();

    $newId = $conn->lastInsertId();
    $container->get('flash')->addMessage('success', 'Страница успешно добавлена');

    return $response->withHeader('Location', "/urls/$newId")->withStatus(302);
});

$app->get('/urls', function (Request $request, Response $response) use ($container, $conn) {
    $sql = "SELECT * FROM urls ORDER BY created_at DESC";
    $stmt = $conn->query($sql);
    $urls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $params = ['urls' => $urls];

    return $container->get('renderer')->render($response, 'urls.phtml', $params);
});

$app->get('/urls/{id}', function (Request $request, Response $response, $args) use ($container, $conn) {
    $id = $args['id'];

    $sql = "SELECT * FROM urls WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $url = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$url) {
        return $response->withStatus(404);
    }
    $messages = $container->get('flash')->getMessages();

    $params = [
        'url' => $url,
        'flash' => $messages
    ];

    return $container->get('renderer')->render($response, 'url.phtml', $params);
});

$app->run();
