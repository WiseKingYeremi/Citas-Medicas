<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\DataService;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\GuzzleException;
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
            return $this->json($response, [
                'error' => 'El username y la contraseña son obligatorios',
            ], 422);
        }

        try {
            $service = $this->container->get(DataService::class);
            $upstream = $service->post('/auth/credentials', json_encode([
                'username' => $username,
                'password' => $password,
            ], JSON_THROW_ON_ERROR));

            $status = $upstream->getStatusCode();
            if ($status >= 500) {
                return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
            }

            if ($status !== 200) {
                return $this->json($response, ['error' => 'Credenciales inválidas'], 401);
            }

            $credentials = json_decode((string) $upstream->getBody(), true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($credentials) || !isset($credentials['id'], $credentials['username'], $credentials['rol_id'])) {
                return $this->json($response, ['error' => 'Respuesta inválida del servicio de datos.'], 502);
            }

            $now = time();
            $config = $this->container->get('config');
            $payload = [
                'iss' => 'citas-medicas-business',
                'iat' => $now,
                'exp' => $now + $config->jwt_ttl,
                'sub' => (string) $credentials['id'],
                'username' => $credentials['username'],
                'rol_id' => (int) $credentials['rol_id'],
            ];

            return $this->json($response, [
                'token' => JWT::encode($payload, $config->key, 'HS256'),
                'expires_in' => $config->jwt_ttl,
                'user' => [
                    'id' => (int) $credentials['id'],
                    'username' => $credentials['username'],
                    'rol_id' => (int) $credentials['rol_id'],
                ],
            ], 200);
        } catch (GuzzleException | \JsonException) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible.'], 502);
        }
    }

    private function json(Response $response, array $payload, int $status): Response
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }
}