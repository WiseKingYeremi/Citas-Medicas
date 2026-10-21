<?php

namespace App\controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use App\Models\PacienteModel;
use App\Models\User;
use Illuminate\Database\QueryException;
use RuntimeException;

class Paciente {
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function read(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');

        if (isset($args['id'])) {
            $paciente = PacienteModel::query()
            ->select(['id', 'usuario_id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono'])
            ->find($args['id']);

            return $this->json($response, ['data' => $paciente], 200);
        }

        $pacientes = PacienteModel::query()
            ->select(['id', 'usuario_id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono'])
            ->get()
            ->toArray();

        return $this->json($response, ['data' => $pacientes], 200);
    }

    public function filter(Request $request, Response $response, array $args): Response {
    $this->container->get('eloquent');

    $offset = filter_var($args['offset'] ?? 0, FILTER_VALIDATE_INT);
    $limit = filter_var($args['limit'] ?? 20, FILTER_VALIDATE_INT);

    if ($offset === false || $offset < 0 || $limit === false || $limit < 1 || $limit > 100) {
        return $this->json($response, ['error' => 'Parámetros inválidos.'], 422);
    }

    $params = $request->getQueryParams();
    $name = trim((string) ($params['nombre_completo'] ?? ''));
    $cedula = trim((string) ($params['cedula'] ?? ''));

    $query = PacienteModel::query()
        ->select(['id', 'usuario_id', 'cedula', 'nombre_completo', 'fecha_nacimiento', 'telefono']);

    if ($name !== '') {
        $query->where('nombre_completo', 'like', "%{$name}%");
    }

    if ($cedula !== '') {
        $query->where('cedula', 'like', "%{$cedula}%");
    }

    $total = (clone $query)->count('id');

    $pacientes = $query
        ->orderBy('nombre_completo')
        ->offset($offset)
        ->limit($limit)
        ->get()
        ->toArray();

    return $this->json($response, [
        'data' => $pacientes,
        'pagination' => [
            'offset' => $offset,
            'limit' => $limit,
            'total' => $total
        ]
    ], 200);
}

    public function create(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $errors = [];
        $cedula = trim((string) ($data['cedula'] ?? ''));
        $fullname = trim((string) ($data['nombre_completo'] ?? ''));
        $birthdate = trim((string) ($data['fecha_nacimiento'] ?? ''));
        $phone = isset($data['telefono']) ? trim((string) $data['telefono']) : null;
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($cedula === '' || mb_strlen($cedula) > 20) {
            $errors['cedula'] = 'Es un campo obligatorio y puede tener máximo 20 caracteres';
        }

        if ($fullname === '' || mb_strlen($fullname) > 150) {
            $errors['nombre_completo'] = 'Es un campo obligatorio y puede tener máximo 150 caracteres';
        }

        if ($birthdate === '' || !$this->isValidDate($birthdate)) {
            $errors['fecha_nacimiento'] = 'Es un campo obligatorio y debe tener el formato YYYY-MM-DD';
        }

        if ($username === '' || mb_strlen($username) > 50) {
            $errors['username'] = 'Es un campo obligatorio y puede tener máximo 50 caracteres';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'Debe tener al menos 8 caracteres';
        }

        if ($phone !== null && $phone === '') {
            $phone = null;
        } elseif ($phone !== null && mb_strlen($phone) > 20) {
            $errors['telefono'] = 'Debe tener máximo 20 caracteres';
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            $eloquent = $this->container->get('eloquent');
            $paciente = $eloquent->connection()->transaction(
                static function () use ($cedula, $fullname, $birthdate, $phone, $username, $password): PacienteModel {
                    $roleId = User::query()
                        ->from('roles')
                        ->where('nombre', 'Paciente')
                        ->value('id');

                    if ($roleId === null) {
                        throw new RuntimeException('El rol Paciente no está configurado.');
                    }

                    $user = User::create([
                        'username' => $username,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'rol_id' => $roleId,
                        'activo' => true,
                    ]);

                    return PacienteModel::create([
                        'usuario_id' => $user->id,
                        'cedula' => $cedula,
                        'nombre_completo' => $fullname,
                        'fecha_nacimiento' => $birthdate,
                        'telefono' => $phone,
                    ]);
                }
            );
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                $message = (string) ($exception->errorInfo[2] ?? '');

                if (str_contains($message, 'username')) {
                    return $this->json($response, ['error' => 'El nombre de usuario ya está registrado.'], 409);
                }

                return $this->json($response, ['error' => 'La cédula ya está registrada.'], 409);
            }

            throw $exception;
        }

        return $this->json($response, ['data' => $paciente->toArray()], 201);
    }

    public function update(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');

        if (!isset($args['id'])) {
            return $this->json($response, ['error' => 'ID de paciente requerido.'], 400);
        }

        $paciente = PacienteModel::find($args['id']);

        if ($paciente === null) {
            return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
        }

        $data = $request->getParsedBody();
        $data = is_array($data) ? $data : [];

        $errors = [];

        if (array_key_exists('nombre_completo', $data)) {
            $fullname = trim((string) $data['nombre_completo']);
            if ($fullname === '' || mb_strlen($fullname) > 150) {
                $errors['nombre_completo'] = 'Debe ser no vacío y máximo 150 caracteres.';
            } else {
                $paciente->nombre_completo = $fullname;
            }
        }

        if (array_key_exists('cedula', $data)) {
            $cedula = trim((string) $data['cedula']);
            if ($cedula === '' || mb_strlen($cedula) > 20) {
                $errors['cedula'] = 'Debe ser no vacía y máximo 20 caracteres.';
            } else {
                $paciente->cedula = $cedula;
            }
        }

        if (array_key_exists('fecha_nacimiento', $data)) {
            $birthdate = trim((string) $data['fecha_nacimiento']);
            if ($birthdate === '' || !$this->isValidDate($birthdate)) {
                $errors['fecha_nacimiento'] = 'Debe tener formato YYYY-MM-DD.';
            } else {
                $paciente->fecha_nacimiento = $birthdate;
            }
        }

        if (array_key_exists('telefono', $data)) {
            $phone = trim((string) $data['telefono']);
            if ($phone !== '' && mb_strlen($phone) > 20) {
                $errors['telefono'] = 'Debe tener máximo 20 caracteres.';
            } else {
                $paciente->telefono = $phone === '' ? null : $phone;
            }
        }

        if ($errors !== []) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        try {
            $paciente->save();
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                return $this->json($response, ['error' => 'La cédula ya está registrada.'], 409);
            }

            throw $exception;
        }

        return $this->json($response, ['data' => $paciente->toArray()], 200);
    }

    public function delete(Request $request, Response $response, array $args): Response {
        $eloquent = $this->container->get('eloquent');

        if (isset($args['id'])) {
            $paciente = PacienteModel::find($args['id']);

            if ($paciente === null) {
                return $this->json($response, ['error' => 'Paciente no encontrado.'], 404);
            }

            $citasCount = $eloquent->table('citas')->where('paciente_id', $paciente->id)->count();

            if ($citasCount > 0) {
                return $this->json($response, ['error' => 'No se puede eliminar el paciente porque tiene citas asignadas'], 409);
            }

            $eloquent->connection()->transaction(static function () use ($paciente): void {
                $usuarioId = $paciente->usuario_id;

                $paciente->delete();

                if ($usuarioId !== null) {
                    $usuario = User::find($usuarioId);
                    if ($usuario !== null) {
                        $usuario->delete();
                    }
                }
            });
        }

        return $this->json($response, ['message' => 'Paciente eliminado correctamente'], 200);
    }

    private function isValidDate(string $date): bool {
        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function json(Response $response, array $payload, int $status): Response {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}