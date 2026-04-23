<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;

$startTime = microtime(true);

include '../src/Web/bootstrap.php';
ini_set('session.save_path', SITE_HOME.'/sessions');
ini_set('session.cookie_path', BASE_URI);
session_start();

$REQUEST = ServerRequest::fromGlobals();
$matcher = $ROUTES->getMatcher();
$ROUTE   = $matcher->match($REQUEST);

if ($ROUTE) {
    $p          = pathinfo($ROUTE->name);
    $resource   = $p['filename'];
    $permission = $p['extension'];
    $role       = isset($_SESSION['USER']) ? ($_SESSION['USER']['role'] ?? 'Staff') : 'Anonymous';
    if (   $ACL->hasResource($resource)
        && $ACL->isAllowed($role, $resource, $permission)) {

        $controller = $ROUTE->handler;
        $c = new $controller();
        if (is_callable($c)) {
            $view = $c(array_merge($_REQUEST, $ROUTE->__get('attributes')));
        }
    }
    else {
        if (!isset($_SESSION['USER'])) {
            $return_url = \Web\View::current_url();
            $login      = \Web\View::generateUrl('login.index')."?return_url=$return_url";
            header("Location: $login");
            exit();
        }
        else {
            header('HTTP/1.1 403 Forbidden', true, 403);
            $_SESSION['errorMessages'][] = 'noAccessAllowed';
            $view = new \Web\Views\ForbiddenView();
        }
    }
}

if (!isset($view)) {
    header('HTTP/1.1 404 Not Found', true, 404);
    $view = new \Web\Views\NotFoundView();
}

echo $view->render();

if ($view->outputFormat === 'html') {
    $endTime     = microtime(true);
    $processTime = $endTime - $startTime;
    echo "<!-- Process Time: $processTime -->";
}
