<?php
namespace App\Controllers;

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $api) {

    $api->post('/auth/credentials', Auth::class . ':credentials');

    $api->group('/medicos', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Medico::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Medico::class . ':filter');
        $endpoint->post('', Medico::class . ':create');
        $endpoint->put('/{id}', Medico::class . ':update');
        $endpoint->delete('/{id}', Medico::class . ':delete');
    });

    $api->group('/pacientes', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('[/{id}]', Paciente::class . ':read');
        $endpoint->get('/filter/{offset}/{limit}', Paciente::class . ':filter');
        $endpoint->post('', Paciente::class . ':create');
        $endpoint->put('/{id}', Paciente::class . ':update');
        $endpoint->delete('/{id}', Paciente::class . ':delete');
    });

    $api->group('/citas', function (RouteCollectorProxy $endpoint) {
        $endpoint->get('/paciente/{id}', Cita::class . ':readPaciente');
        $endpoint->get('/medico/{id}', Cita::class . ':readMedico');
        $endpoint->post('', Cita::class . ':create');
    });
});