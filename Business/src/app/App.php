<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use JimTools\JwtAuth\Decoder\FirebaseDecoder;
use JimTools\JwtAuth\Exceptions\AuthorizationException;
use JimTools\JwtAuth\Middleware\JwtAuthentication;
use JimTools\JwtAuth\Options;
use JimTools\JwtAuth\Rules\RequestPathRule;
use JimTools\JwtAuth\Secret;

require __DIR__ . '/../../vendor/autoload.php';


$container = new Container();

$dotenv = Dotenv\Dotenv::createImmutable('/var/www/html');
$dotenv->load();

AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

require_once "routes.php";
require_once "config.php";

$app->add(new JwtAuthentication(
    new Options(isSecure: false),
    new FirebaseDecoder(new Secret($_ENV['KEY'], 'HS256')),
    [new RequestPathRule(['/'], ['/api/auth/login', '/api/medicos'])]
));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(AuthorizationException::class,
    function ($request, AuthorizationException $exception, bool $displayErrorDetails) use ($app) {
        $response = $app->getResponseFactory()->createResponse(401);
        $response->getBody()->write(json_encode([
            'error' => 'Token invalido o ausente.',
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
);

// CORS: debe ir al final (después del JWT y del manejo de errores)
// para que sea el primer middleware en ejecutarse.
$app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app): ResponseInterface {
    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = array_map(
        'trim',
        explode(',', $_ENV['CORS_ALLOWED_ORIGIN'] ?? 'http://localhost:4200,http://localhost:4300')
    );
    $allowedOrigin = in_array($origin, $allowedOrigins, true) ? $origin : null;

    if ($request->getMethod() === 'OPTIONS' && $allowedOrigin !== null) {
        $response = $app->getResponseFactory()->createResponse(204);
    } else {
        $response = $handler->handle($request);
    }

    if ($allowedOrigin === null) {
        return $response;
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Accept, Authorization, Content-Type, Origin, X-Requested-With')
        ->withHeader('Vary', 'Origin');
});

$app->run();