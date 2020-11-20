<?php
namespace Microcms;

use Zend\Mail;
use Zend\Mail\Transport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;

require_once 'Registry.php';


/**
 * Class Common
 * @package Form
 */
class Common {

    protected $config;


    /**
     * Common constructor.
     */
    public function __construct() {

        $this->config = Registry::get('config');
    }


    /**
     * Отправка письма
     * @param string $to
     * @param string $subj
     * @param string $body
     * @param string $from
     * @param string $cc
     * @param string $bcc
     * @param array $files
     * @return bool Успешна или нет отправка
     * @throws \Zend_Exception
     */
    protected function sendMail($to, $subj, $body, $from = '', $cc = '', $bcc = '', $files = []) {

        $message = new Mail\Message();
        $message->setEncoding('UTF-8');

        if (empty($from)) {
            if ($this->config->system && $this->config->system->host) {
                $from = "noreply@" . $this->config->system->host;
            } else {
                $from = "noreply@" . $_SERVER['SERVER_NAME'];
            }
        }

        $from_email           = trim($from);
        $from_name            = '';
        $from_address_explode = explode('<', $from);

        if ( ! empty($from_address_explode[1])) {
            $from_email = trim($from_address_explode[1], '> ');
            $from_name  = trim($from_address_explode[0]);
        }
        $message->setFrom($from_email, $from_name);



        // TO
        $to_addresses_explode = explode(',', $to);
        foreach ($to_addresses_explode as $to_address) {
            if (empty(trim($to_address))) {
                continue;
            }

            $to_email           = trim($to_address);
            $to_name            = '';
            $to_address_explode = explode('<', $to_address);

            if ( ! empty($to_address_explode[1])) {
                $to_email = trim($to_address_explode[1], '> ');
                $to_name  = trim($to_address_explode[0]);
            }

            $message->addTo($to_email, $to_name);
        }

        // CC
        if ( ! empty($cc)) {
            $cc_addresses_explode = explode(',', $cc);
            foreach ($cc_addresses_explode as $cc_address) {
                if (empty(trim($cc_address))) {
                    continue;
                }

                $cc_email           = trim($cc_address);
                $cc_name            = '';
                $cc_address_explode = explode('<', $cc_address);

                if ( ! empty($cc_address_explode[1])) {
                    $cc_email = trim($cc_address_explode[1], '> ');
                    $cc_name  = trim($cc_address_explode[0]);
                }

                $message->addCc($cc_email, $cc_name);
            }
        }


        // BCC
        if ( ! empty($bcc)) {
            $bcc_addresses_explode = explode(',', $bcc);
            foreach ($bcc_addresses_explode as $bcc_address) {
                if (empty(trim($bcc_address))) {
                    continue;
                }

                $bcc_email           = trim($bcc_address);
                $bcc_name            = '';
                $bcc_address_explode = explode('<', $bcc_address);

                if ( ! empty($bcc_address_explode[1])) {
                    $bcc_email = trim($bcc_address_explode[1], '> ');
                    $bcc_name  = trim($bcc_address_explode[0]);
                }

                $message->addBcc($bcc_email, $bcc_name);
            }
        }

        $message->setSubject($subj);

        $parts = [];

        $html = new MimePart($body);
        $html->type     = Mime::TYPE_HTML;
        $html->charset  = 'utf-8';
        $html->encoding = Mime::ENCODING_QUOTEDPRINTABLE;

        $parts[] = $html;

        if ( ! empty($files)) {
            foreach ($files as $file) {

                $attach_file              = new MimePart($file['content']);
                $attach_file->type        = $file['mimetype'];
                $attach_file->filename    = $file['name'];
                $attach_file->disposition = Mime::DISPOSITION_ATTACHMENT;
                $attach_file->encoding    = Mime::ENCODING_BASE64;

                $parts[] = $attach_file;
            }
        }

        $body = new MimeMessage();
        $body->setParts($parts);

        $message->setBody($body);

        $transport = new Transport\Sendmail();

        if ( ! empty($this->config->system) &&
             ! empty($this->config->system->email) &&
             ! empty($this->config->system->email->server)
        ) {
            $config_smtp = [
                'host' => $this->config->system->email->server
            ];

            if ( ! empty($this->config->system->email->port)) {
                $config_smtp['port'] = $this->config->system->email->port;
            }

            if ( ! empty($this->config->system->email->auth)) {
                $config_smtp['connection_class'] = $this->config->system->email->auth;

                if ( ! empty($this->config->system->email->username)) {
                    $config_smtp['connection_config']['username'] = $this->config->system->email->username;
                }
                if ( ! empty($this->config->system->email->password)) {
                    $config_smtp['connection_config']['password'] = $this->config->system->email->password;
                }
                if ( ! empty($this->config->system->email->ssl)) {
                    $config_smtp['connection_config']['ssl'] = $this->config->system->email->ssl;
                }
            }


            $options   = new Transport\SmtpOptions($config_smtp);
            $transport = new Transport\Smtp();
            $transport->setOptions($options);
        }

        $transport->send($message);

        return true;
    }


    /**
     * @param string          $message
     * @param array|Exception $data
     * @return bool
     */
    protected function sendErrorMessage($message, $data = []) {

        $admin_email = $this->config->system && $this->config->system->admin_email
            ? $this->config->system->admin_email
            : false;

        if (empty($admin_email)) {
            return false;
        }

        $cabinet_name = ! empty($this->config->system) ? $this->config->system->name : 'Без названия';
        $cabinet_host = ! empty($this->config->system) ? $this->config->system->host : '';
        $protocol     = ! empty($this->config->system) && $this->config->system->https ? 'https' : 'http';


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
            Ошибка в системе <a href=\"{$protocol}://{$cabinet_host}\">{$cabinet_host}</a><br><br>
            
            <small style=\"color:#777\">{$error_date}</small><br>
            <b>{$message}</b><br><br>        
            
            {$data_msg}
        ";


        return $this->sendMail($admin_email, $cabinet_name . ': Ошибка', $body);
    }
}