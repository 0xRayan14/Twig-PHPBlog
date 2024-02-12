<?php

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Error\LoaderError;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

try {
    $twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);
} catch (LoaderError $e) {
}
$app->add(TwigMiddleware::create($app, $twig));

$databasePath = __DIR__ . '/../database.db';
$pdo = new PDO("sqlite:$databasePath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$app->get('/', function ($request, $response) use ($twig) {
    return $twig
        ->render($response, 'index.twig');
});

$app->post('/submit-article', function ($request, $response) use ($pdo) {
    $title = $request->getParsedBody()['article_title'];
    $text = $request->getParsedBody()['article_text'];
    $category = $request->getParsedBody()['article_category'];

    $stmt = $pdo->prepare("INSERT INTO articles (title, text, category) VALUES (?, ?, ?)");
    $stmt->execute([$title, $text, $category]);

    $_SESSION['messages'] = ['Article successfully added'];

    return $response
        ->withHeader('Location', '/articles')
        ->withStatus(302);
});

$app->get('/articles', function ($request, $response) use ($twig, $pdo) {
    $messages = $_SESSION['messages'] ?? [];
    unset($_SESSION['messages']);

    $stmt = $pdo->query("SELECT * FROM articles");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $twig
        ->render($response, 'articles.twig', ['articles' => $articles, 'messages' => $messages]);
});



$app->post('/delete-article', function ($request, $response) use ($pdo) {
    $articleId = $request->getParsedBody()['article_id'];

    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);

    $_SESSION['messages'] = ['Article successfully deleted'];
    return $response
        ->withHeader('Location', '/articles')->withStatus(302);
});


$app->post('/edit-article', function ($request, $response) use ($pdo, $twig) {
    $articleId = $request->getParsedBody()['article_id'];

    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    return $twig->render($response, 'edit-article.twig', ['article' => $article]);
});

$app->post('/update-article', function ($request, $response) use ($pdo) {
    $articleId = $request->getParsedBody()['article_id'];
    $title = $request->getParsedBody()['article_title'];
    $text = $request->getParsedBody()['article_text'];

    $stmt = $pdo->prepare("UPDATE articles SET title = ?, text = ? WHERE id = ?");
    $stmt->execute([$title, $text, $articleId]);

    $_SESSION['messages'] = ['Article successfully updated'];
    return $response
        ->withHeader('Location', '/articles')->withStatus(302);
});

$app->get('/search', function ($request, $response) use ($twig, $pdo) {
    $title = $request->getQueryParams()['title'];

    $stmt = $pdo->prepare("SELECT * FROM articles WHERE title LIKE ?");
    $stmt->execute(["%$title%"]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $twig
        ->render($response, 'articles.twig', ['articles' => $articles]);
});


$app->get('/sort-az', function ($request, $response) use ($twig, $pdo) {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY title ");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['messages'] = ['Article successfully sorted'];

    return $twig
        ->render($response, 'articles.twig', ['articles' => $articles]);
});

$app->get('/sort-za', function ($request, $response) use ($twig, $pdo) {
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY title DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['messages'] = ['Article successfully sorted'];

    return $twig
        ->render($response, 'articles.twig', ['articles' => $articles]);
});

$app->run();