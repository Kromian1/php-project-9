<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Db\Connection;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$conn = new Connection();
$conn->get();

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
    $messages = $this->get('flash')->getMessages();
    $params = ['flash' => $messages];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
});

$app->post('/urls', function (Request $request, Response $response) use ($container, $conn) {
    $data = $request->getParsedBody();





    $sql = "INSERT INTO urls (name) VALUES (:url)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':url', $data['url']);
    $stmt->execute();
});

$app->run();
