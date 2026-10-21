<?php
namespace App\Controllers;

use Slim\Routing\RouteCollectorProxy;

$app->group('/api', function (RouteCollectorProxy $api) {

    $api->post('/auth/login', Auth::class . ':credentials');

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
        $endpoint->get('/paciente/{id}', Citas::class . ':readPaciente');
        $endpoint->get('/medico/{id}', Citas::class . ':readMedico');
        $endpoint->post('', Citas::class . ':create');
    });
});