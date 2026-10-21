<?php

declare(strict_types=1);

namespace App\Validators;

use Respect\Validation\Validator;
use Respect\Validation\Exceptions\ValidationException;

final class MedicoValidator
{
    public function validate(array $data): array
    {
        $validator = Validator::key('especialidad_id', Validator::intVal()->positive())
            ->key(
                'nombre_completo',
                Validator::stringType()
                    ->notEmpty()
                    ->length(6, 150)
                    ->regex('/^[A-Za-zÑñÁÉÍÓÚáéíóú]{2,}(?: [A-Za-zÑñÁÉÍÓÚáéíóú]{2,}){1,3}$/u')
            )
            ->key('licencia', Validator::stringType()->notEmpty()->length(8, 50))
            ->key(
                'username', 
                Validator::stringType()
                    ->notEmpty()
                    ->length(8, 16)
                    ->regex('/^[a-z0-9]{8,16}$/')
            )
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