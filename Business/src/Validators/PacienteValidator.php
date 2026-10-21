<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

final class PacienteValidator
{
    public function validate(array $data): array
    {
        $validator = Validator::key('cedula', Validator::stringType()->notEmpty()->length(1, 20))
            ->key(
                'nombre_completo',
                Validator::stringType()
                    ->notEmpty()
                    ->length(1, 150)
                    ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/u')
            )
            ->key('fecha_nacimiento', Validator::date('Y-m-d'))
            ->key('username', Validator::stringType()->notEmpty()->length(1, 50))
            ->key(
                'password',
                Validator::stringType()
                    ->regex('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[#@$*])[\s\S]{8,16}$/')
            )
            ->key('telefono', Validator::optional(
                Validator::stringType()
                    ->regex('/^([2-9][0-9]{3}-[0-9]{4})$/')
                    ->length(1, 20)
            ), false);

        try {
            $validator->assert($data);
        } catch (ValidationException $exception) {
            return $exception->getMessages();
        }

        return [];
    }
}