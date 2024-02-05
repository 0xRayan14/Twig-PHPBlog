<?php

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;


require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
$app->add(TwigMiddleware::create($app, $twig));

$databasePath = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$databasePath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Define routes
$app->get('/', function ($request, $response) use ($twig) {
    return $twig->render($response, 'index.twig');
});

$app->post('/submit-article', function ($request, $response) use ($pdo) {
    // Retrieve form data
    $title = $request->getParsedBody()['article_title'];
    $text = $request->getParsedBody()['article_text'];

    // Insert data into the 'articles' table
    $stmt = $pdo->prepare("INSERT INTO articles (title, text) VALUES (?, ?)");
    $stmt->execute([$title, $text]);

    // Redirect to the '/articles' route to display the list of articles
    return $response
        ->withHeader('Location', '/articles')
        ->withStatus(302); // 302 Found (temporary redirect)
});



$app->get('/articles', function ($request, $response) use ($twig, $pdo) {
    // Fetch all articles from the 'articles' table
    $stmt = $pdo->query("SELECT * FROM articles");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Render the 'articles.twig' template with the list of articles
    return $twig->render($response, 'articles.twig', ['articles' => $articles]);
});




$app->run();
