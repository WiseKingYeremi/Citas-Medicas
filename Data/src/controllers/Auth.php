<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class Auth
{
    public function __construct(private ContainerInterface $container) {}

    public function credentials(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->json($response, ['error' => 'Datos incompletos'], 422);
        }

        $cfg = $this->container->get('config_bd');
        $pdo = new PDO(
            "mysql:host={$cfg->host};dbname={$cfg->database};charset={$cfg->charset}",
            $cfg->username,
            $cfg->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash, rol_id
            FROM usuarios
            WHERE username = :username AND activo = 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->json($response, ['error' => 'Credenciales inválidas'], 401);
        }

        return $this->json($response, [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'rol_id' => (int) $user['rol_id'],
        ], 200);
    }

    private function json(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}