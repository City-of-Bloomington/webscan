<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 * @var Aura\Router\RouterContainer $ROUTES
 */
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Role\GenericRole as Role;
use Laminas\Permissions\Acl\Resource\GenericResource as Resource;

$ACL = new Acl();
$ACL->addRole(new Role('anonymous'))
    ->addRole(new Role('authenticated'))
    ->addRole(new Role('project_editor'))
    ->addRole(new Role('utilities_project_editor'))
    ->addRole(new Role('news'))
    ->addRole(new Role('web_admins'))
    ->addRole(new Role('webmaster'))
    ->addRole(new Role('administrator'));

/**
 * Create resources for all the routes
 */
foreach ($ROUTES->getMap()->getRoutes() as $r) {
    $p = pathinfo($r->name);
    $resource = $p['filename'];
    if (!$ACL->hasResource($resource)) {
         $ACL->addResource(new Resource($resource));
    }
}

/**
 * Assign permissions to the resources
 */
// Permissions for unauthenticated browsing
$ACL->allow(null,  'home');
$ACL->allow(null, 'content');

// Administrator is allowed access to everything
$ACL->allow('administrator');
