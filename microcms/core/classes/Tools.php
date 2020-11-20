<?php
namespace Microcms;


/**
 * Класс, для полезных инструментов 
 */
class Tools {


    /**
     * Parses INI file adding extends functionality via ":base" postfix on namespace.
     *
     * @param  string $filename
     * @param  string $section
     * @return array
     * @throws \Exception
     */
    public static function getConfig($filename, $section = null) {

        $p_ini  = parse_ini_file($filename, true);
        $config = array();

        foreach ($p_ini as $namespace => $properties) {
            if (is_array($properties)) {
                @list($name, $extends) = explode(':', $namespace);
                $name = trim($name);
                $extends = trim($extends);
                // create namespace if necessary
                if (!isset($config[$name])) $config[$name] = array();
                // inherit base namespace
                if (isset($p_ini[$extends])) {
                    foreach ($p_ini[$extends] as $key => $val)
                        $config[$name] = self::processKey($config[$name], $key, $val);;
                }
                // overwrite / set current namespace values
                foreach ($properties as $key => $val)
                    $config[$name] = self::processKey($config[$name], $key, $val);
            } else {
                if ( ! isset($config['global'])) {
                    $config['global'] = array();
                }
                $parsed_key = self::processKey(array(), $namespace, $properties);
                $config['global'] = self::arrayMergeRecursiveDistinct($config['global'], $parsed_key);
            }
        }
        if ($section) {
            if (isset($config[$section])) {
                return $config[$section];
            } else {
                throw new \Exception("Section '{$section}' not found");
            }
        } else {
            if (count($config) === 1 && isset($config['global'])) {
                return $config['global'];
            }

            return $config;
        }
    }


    /**
     * Print link to CSS file
     *
     * @param string $href - CSS filename
     */
    public static function printCss($href) {
        if (strpos($href, '?')) {
            $explode_href = explode('?', $href, 2);
            $href .= file_exists(DOC_ROOT . $explode_href[0])
                ? '&_=' . crc32(md5_file(DOC_ROOT . $explode_href[0]))
                : '';
        } else {
            $href .= file_exists(DOC_ROOT . $href)
                ? '?_=' . crc32(md5_file(DOC_ROOT . $href))
                : '';
        }
        echo '<link href="' . $href . '" type="text/css" rel="stylesheet" />';
    }


    /**
     * Print link to JS file
     *
     * @param string $src - JS filename
     */
    public static function printJs($src) {
        echo '<script type="text/javascript" language="JavaScript" src="' . $src . '"></script>';
    }


    /**
     * Функция склонения числительных в русском языке
     * @param int   $number Число которое нужно просклонять
     * @param array $titles Массив слов для склонения
     * @return string
     */
    public static function declNum($number, $titles) {

        $cases = array(2, 0, 1, 1, 1, 2);
        $num = abs($number);
        return $titles[($num % 100 > 4 && $num % 100 < 20) ? 2 : $cases[min($num % 10, 5)]];
    }


    /**
     * Добавляет разделитель через каждые 3 символа в указанном числе
     * @param string $_
     * @param string $del
     * @return string
     */
    public static function commafy($_, $del = ';psbn&') {
        return strrev( (string)preg_replace( '/(\d{3})(?=\d)(?!\d*\.)/', '$1' . $del , strrev( $_ ) ) );
    }


    /**
     * @param string          $message
     * @param array|\Exception $data
     * @return bool
     * @throws \PHPMailer\PHPMailer\Exception
     */
    private function sendErrorMessage($message, $data = []) {

        $admin_email = $this->conf['email']['to'];

        if (empty($admin_email)) {
            return false;
        }

        $data_msg = '';

        if ($data) {
            if ($data instanceof \Exception) {
                $data_msg .= $data->getMessage() . "<br>";
                $data_msg .= '<b>' . $data->getFile() . ': ' . $data->getLine() . "</b><br><br>";
                $data_msg .= '<pre>' . $data->getTraceAsString() . '</pre>';

            } else {
                $data_msg = '<pre>' . print_r($data, true) . '</pre>';
            }
        }

        $error_date = date('d.m.Y H:i:s');

        $body = "
            Ошибка в системе 
            
            <small style=\"color:#777\">{$error_date}</small><br>
            <b>{$message}</b><br><br>        
            
            {$data_msg}
        ";

        $email = new \PHPMailer\PHPMailer\PHPMailer();
        $email->CharSet  = "UTF-8";
        $email->Encoding = 'base64';
        $email->IsHTML(true);
        $email->SetFrom($this->conf['email']['from']);
        $email->Subject   = $message;
        $email->Body      = $body;
        $email->AddAddress( $admin_email );
        $email->Send();

        return true;
    }


    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    private static function arrayMergeRecursiveDistinct(array &$array1, array &$array2) {
        $merged = $array1;

        foreach ( $array2 as $key => &$value ) {
            if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ) {
                $merged [$key] = self::arrayMergeRecursiveDistinct ($merged [$key], $value );
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }


    /**
     * Процесс разделения на субсекции ключей конфига
     * @param array $config
     * @param string $key
     * @param string $val
     * @return array
     */
    private static function processKey($config, $key, $val) {
        $nest_separator = '.';
        if (strpos($key, $nest_separator) !== false) {
            $pieces = explode($nest_separator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if ( ! isset($config[$pieces[0]])) {
                    if ($pieces[0] === '0' && ! empty($config)) {
                        // convert the current values in $config into an array
                        $config = array($pieces[0] => $config);
                    } else {
                        $config[$pieces[0]] = array();
                    }
                }
                $config[$pieces[0]] = self::processKey($config[$pieces[0]], $pieces[1], $val);
            }
        } else {
            $config[$key] = $val;
        }
        return $config;
    }
}