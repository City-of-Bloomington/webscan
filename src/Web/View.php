<?php
/**
 * @copyright 2026 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
namespace Web;

use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

abstract class View
{
    protected $vars         = [];
    protected $twig;
    public    $outputFormat = 'html';

    abstract public function render();

    /**
     * Configures the gettext translations
     */
    public function __construct()
    {
        // Twig templates
        $this->outputFormat = !empty($_REQUEST['format']) ? $_REQUEST['format'] : 'html';
        $tpl = [];

        if (defined('THEME')) {
            $dir = SITE_HOME.'/Themes/'.THEME;

            // Twig Templates
            if (is_dir ( "$dir/templates")) {
                $tpl[] = "$dir/templates";
            }
        }

        // Twig Templates
        $tpl[]      = APPLICATION_HOME.'/templates';
        $loader     = new FilesystemLoader($tpl);
        $this->twig = new Environment($loader, ['cache'            => false,
                                                'strict_variables' => true,
                                                'debug'            => true]);

        global $REQUEST, $ROUTE;

        $this->twig->addGlobal('APPLICATION_NAME', APPLICATION_NAME);
        $this->twig->addGlobal('VERSION',          VERSION);
        $this->twig->addGlobal('BASE_URL',         BASE_URL);
        $this->twig->addGlobal('BASE_URI',         BASE_URI);
        $this->twig->addGlobal('USWDS_URL',        USWDS_URL);
        $this->twig->addGlobal('DRUPAL_SITE',      DRUPAL_SITE);
        $this->twig->addGlobal('REQUEST',          $REQUEST);
        $this->twig->addGlobal('ROUTE_NAME',       $ROUTE ? $ROUTE->name : null);
        $this->twig->addGlobal('DATE_FORMAT',      DATE_FORMAT);
        $this->twig->addGlobal('TIME_FORMAT',      TIME_FORMAT);
        $this->twig->addGlobal('DATETIME_FORMAT',  DATETIME_FORMAT);
        $this->twig->addGlobal('LANG',             strtolower(substr(LOCALE, 0, 2)));

        if (isset($_SESSION['USER'])) {
            $this->twig->addGlobal('USER', $_SESSION['USER']);
        }
        if (isset($_SESSION['errorMessages'])) {
            $this->twig->addGlobal('ERROR_MESSAGES', $_SESSION['errorMessages']);
            unset($_SESSION['errorMessages']);
        }
        $this->twig->addExtension(new DebugExtension());

        $this->twig->addFunction(new TwigFunction('uri',         [$this, 'generateUri']));
        $this->twig->addFunction(new TwigFunction('url',         [$this, 'generateUrl']));
        $this->twig->addFunction(new TwigFunction('isAllowed',   [$this, 'isAllowed'  ]));
        $this->twig->addFunction(new TwigFunction('http_build_query', 'http_build_query'));
    }

    /**
     * Creates a URI for a named route
     *
     * This imports the $ROUTES global variable and calls the
     * generate function on it.
     *
     * @see https://github.com/auraphp/Aura.Router/tree/2.x
     */
    public static function generateUri(string $route_name, ?array $route_params=[], ?array $query_params=null): string
    {
        global $ROUTES;
        $helper = $ROUTES->newRouteHelper();
        $uri    = $helper($route_name, $route_params);
        return $query_params ? $uri.'?'.http_build_query($query_params) : $uri;
    }

    public static function generateUrl(string $route_name, ?array $route_params=[], ?array $query_params=[]): string
    {
        return "https://".BASE_HOST.self::generateUri($route_name, $route_params, $query_params);
    }

    public static function current_url(): string
    {
        global $REQUEST;

        $url = $REQUEST->getUri()->getPath();
        if ($_SERVER['QUERY_STRING']) {
            $s = preg_replace('/[^a-zA-Z0-9\=\;\&\+]/', '', $_SERVER['QUERY_STRING']);
            $p = [];
            parse_str($s, $p);
            $url.='?'.http_build_query($p);
        }
        return $url;
    }

    public static function isAllowed(string $resource, ?string $action=null): bool
    {
        global $ACL;
        $role = 'Anonymous';
        if (isset  ($_SESSION['USER']) && $_SESSION['USER']->getRole()) {
            $role = $_SESSION['USER']->getRole();
        }
        return $ACL->isAllowed($role, $resource, $action);
    }
}
