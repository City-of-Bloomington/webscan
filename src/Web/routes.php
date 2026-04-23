<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

$ROUTES = new \Aura\Router\RouterContainer(BASE_URI);
$map    = $ROUTES->getMap();
$map->tokens(['id' => '\d+',
             'nid' => '\d+']);

$map->attach('home.', '/', function ($r) {
    $r->get('login',  'login',  Web\Auth\Login\Controller::class);
    $r->get('logout', 'logout', Web\Auth\Logout\Controller::class);
    $r->get('info',   '{id}',   Web\Reports\Info\Controller::class);
    $r->get('index',  '',       Web\Reports\List\Controller::class);
});

$map->attach('content.', '/content', function ($r) {
    $r->get('search', '', Web\Content\Search\Controller::class);
});

$map->attach('departments.', '/departments', function ($r) {
    $r->get('info', '/{id}',  Web\Departments\Info\Controller::class);
    $r->get('index', '',      Web\Departments\List\Controller::class);
});

$map->attach('settings.', '/settings', function ($r) {
    $r->get('index',     ''          , Web\Settings\Index\Controller::class);
});
