<?php
namespace Microcms;

require_once 'Common.php';


/**
 * Class Init
 * @package Microcms
 */
class Init extends Common {


    /**
     * Init constructor.
     */
    public function __construct() {

        parent::__construct();

        if ($this->config->system && $this->config->system->timezone) {
            $tz = $this->config->system->timezone;
            if ( ! empty($tz)) {
                date_default_timezone_set($tz);
            }
        }
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function dispatch() {

        if (PHP_SAPI === 'cli') {
            return $this->dispatchCli();
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            if ( ! empty($_GET['method'])) {
                $method = $_GET['method'];

                if (preg_match('[^a-zA-Z0-9_]', $method)) {
                    http_response_code(404);
                    throw new \Exception('Incorrect handler method');
                }

                require_once __DIR__ . '/../../Handler.php';

                $handler = new Handler();
                if (method_exists($handler, $method)) {
                    return $handler->{$method}();
                } else {
                    http_response_code(404);
                    throw new \Exception('Handler method not found');
                }
            }
        }


        require_once __DIR__ . '/../../Controller.php';
        $controller = new Controller();
        return $controller->dispatch();
    }


    /**
     * Cli
     * @return string
     * @throws \Exception
     */
    private function dispatchCli() {

        $options = getopt('m:a:s:h', [
            'method:',
            'argument:',
            'section:',
            'help',
        ]);

        if (empty($options) || isset($options['h']) || isset($options['help'])) {
            return implode(PHP_EOL, [
                'Microcms',
                'Usage: php index.php [OPTIONS]',
                'Optional arguments:',
                "   -m    --method    Cli method name",
                "   -a    --argument  Parameter in method",
                "   -s    --section   Section name in config file",
                "   -h    --help      Help info",
                "Examples of usage:",
                "php index.php --method run",
                "php index.php --method run --section site.com",
                "php index.php --method run --argument 123" . PHP_EOL,
            ]);
        }

        if (isset($options['m']) || isset($options['method'])) {
            $method = isset($options['method']) ? $options['method'] : $options['m'];

            $arguments = isset($options['argument'])
                ? $options['argument']
                : (isset($options['a']) ? $options['a'] : false);
            $arguments = $arguments === false
                ? []
                : (is_array($arguments) ? $arguments : [$arguments]);


            try {
                $cli_path = __DIR__ . '/../../Cli.php';

                if ( ! file_exists($cli_path)) {
                    throw new \Exception(sprintf("Файл %s не найден", $cli_path));
                }
                require_once($cli_path);

                $class_name = __NAMESPACE__ . '\\Cli';
                if ( ! class_exists($class_name)) {
                    throw new \Exception(sprintf("Класс %s не найден", $class_name));
                }

                $all_class_methods = get_class_methods($class_name);
                if ($parent_class = get_parent_class($class_name)) {
                    $parent_class_methods = get_class_methods($parent_class);
                    $self_methods         = array_diff($all_class_methods, $parent_class_methods);
                } else {
                    $self_methods = $all_class_methods;
                }

                if (array_search($method, $self_methods) === false) {
                    throw new \Exception(sprintf("В классе %s не найден метод %s", $class_name, $method));
                }

                $class_instance = new $class_name();
                $result       = call_user_func_array([$class_instance, $method], $arguments);

                if (is_scalar($result)) {
                    return (string)$result . PHP_EOL;
                }


            } catch (\Exception $e) {
                $message = $e->getMessage();
                return $message . PHP_EOL;
            }
        }

        return PHP_EOL;
    }
}