<?php

namespace App\controllers;

use App\Services\DataService;
use App\Validators\MedicoValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class Medico {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function read(Request $request, Response $response, array $args) {

        try {
            $dataService = $this->container->get(DataService::class);
            $path = isset($args['id']) ? 'medicos/' . rawurlencode($args['id']) : 'medicos';
            $upstream = $dataService->get($path);
            $response->getBody()->write((string)$upstream->getBody());
            return $response->withHeader(
                    'Content-type', $upstream->getHeaderLine('Content-Type') ?: 'application/json; charset=utf-8')
                    ->withStatus($upstream->getStatusCode());
        } catch (ConnectException $e) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function filter(Request $request, Response $response, array $args): Response {

        try {
            $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
            $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

            if ($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100) {
                return $this->json($response, ['error' => 'Parámetros inválidos.'], 422);
            }

            $dataService = $this->container->get(DataService::class);
            $path = 'medicos/filter/' . $offset . '/' . $limit;
            $upstream = $dataService->filter($path, $request->getQueryParams());

            return $this->json(
                $response,
                json_decode((string) $upstream->getBody(), true) ?? [],
                $upstream->getStatusCode()
            );
        } catch (ConnectException $e) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function create(Request $request, Response $response, array $args): Response {

        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errors' => ['body' => 'Debe enviar un objeto JSON válido.']], 422);
            }

            $errors = (new MedicoValidator())->validate($data);
            if ($errors !== []) {
                return $this->json($response, ['errors' => $errors], 422);
            }

            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->post('medicos', json_encode($data, JSON_THROW_ON_ERROR));
            return $this->json($response,
                json_decode((string) $upstream->getBody(), true) ?? [],
                $upstream->getStatusCode());

        } catch (ConnectException $e) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function update(Request $request, Response $response, array $args): Response {

        try {
            $request->getBody()->rewind();
            $body = (string) $request->getBody();
            $data = json_decode($body, true);

            if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
                return $this->json($response, ['errors' => ['body' => 'Debe enviar un objeto JSON válido.']], 422);
            }

            $errors = (new MedicoValidator())->validate($data);
            if ($errors !== []) {
                return $this->json($response, ['errors' => $errors], 422);
            }

            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->put('medicos/' . rawurlencode($args['id']), $body);

            return $this->json($response, json_decode((string) $upstream->getBody(), true) ?? [],
            $upstream->getStatusCode());

        } catch (ConnectException $e) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    public function delete(Request $request, Response $response, array $args): Response {

        try {
            $dataService = $this->container->get(DataService::class);
            $upstream = $dataService->delete('medicos/' . rawurlencode($args['id']));

            return $this->json($response, json_decode((string) $upstream->getBody(), true) ?? [],
            $upstream->getStatusCode());

        } catch (ConnectException $e) {
            return $this->json($response, ['error' => 'El servicio de datos no está disponible'], 502);
        } catch (RequestException $e) {
            return $this->json($response, ['error' => 'No se pudo consultar el servicio de datos'], 502);
        }
    }

    private function json(Response $response, array $payload, int $status): Response {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}