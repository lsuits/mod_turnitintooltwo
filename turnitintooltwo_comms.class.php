<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once($CFG->dirroot.'/mod/turnitintooltwo/sdk/api.class.php');

class turnitintooltwo_comms {

    private $tiiaccountid;
    private $tiiapiurl;
    private $tiisecretkey;
    private $tiiintegrationid;
    private $diagnostic;
    private $langcode;
    
    public function __construct() {
        $config = turnitintooltwo_admin_config();

        $this->tiiintegrationid = 12;
        $this->tiiaccountid = $config->accountid;
        $this->tiiapiurl = (substr($config->apiurl, -1) == '/') ? substr($config->apiurl, 0, -1) : $config->apiurl;
        $this->tiisecretkey = $config->secretkey;
        
        if (empty($this->tiiaccountid) || empty($this->tiiapiurl) || empty($this->tiisecretkey)) {
            turnitintooltwo_print_error( 'configureerror', 'turnitintooltwo' );
        }

        $this->diagnostic = $config->enablediagnostic;
        $this->langcode = $this->get_lang();
    }

    /**
     * Initialise the API object
     *
     * @return object \APITurnitin
     */
    public function initialise_api() {
        global $CFG;

        $api = new TurnitinAPI($this->tiiaccountid, $this->tiiapiurl, $this->tiisecretkey,
                                $this->tiiintegrationid, $this->langcode);
        // Enable logging if diagnostic mode is turned on.
        if ($this->diagnostic) {
            $api->setLogPath($CFG->tempdir.'/turnitintooltwo/logs/');
        }

        // Use Moodle's proxy settings if specified
        if (!empty($CFG->proxyhost)) {
            $api->setProxyHost($CFG->proxyhost);
        }

        if (!empty($CFG->proxyport)) {
            $api->setProxyPort($CFG->proxyport);
        }

        if (!empty($CFG->proxyuser)) {
            $api->setProxyUser($CFG->proxyuser);
        }

        if (!empty($CFG->proxypassword)) {
            $api->setProxyPassword($CFG->proxypassword);
        }

        if (!empty($CFG->proxytype)) {
            $api->setProxyType($CFG->proxytype);
        }

        if (!empty($CFG->proxybypass)) {
            $api->setProxyBypass($CFG->proxybypass);
        }

        if (is_readable("$CFG->dataroot/moodleorgca.crt")) {
            $certificate = realpath("$CFG->dataroot/moodleorgca.crt");
            $api->setSSLCertificate($certificate);
        }

        return $api;
    }

    /**
     * Log API exceptions and print error to screen if required
     *
     * @param object $e
     * @param string $tterrorstr
     * @param boolean $toscreen
     */
    public static function handle_exceptions($e, $tterrorstr = "", $toscreen = true) {
        $errorstr = "";
        if (!empty($tterrorstr)) {
            $errorstr = get_string($tterrorstr, 'turnitintooltwo')."<br/><br/>";
        }

        if (is_callable(array($e, 'getFaultCode'))) {
            $errorstr .= get_string('faultcode', 'turnitintooltwo').": ".$e->getFaultCode()." | ";
        }

        if (is_callable(array($e, 'getFile'))) {
            $errorstr .= get_string('file').": ".$e->getFile()." | ";
        }

        if (is_callable(array($e, 'getLine'))) {
            $errorstr .= get_string('line', 'turnitintooltwo').": ".$e->getLine()." | ";
        }

        if (is_callable(array($e, 'getMessage'))) {
            $errorstr .= get_string('message', 'turnitintooltwo').": ".$e->getMessage()." | ";
        }

        if (is_callable(array($e, 'getCode'))) {
            $errorstr .= get_string('code', 'turnitintooltwo').": ".$e->getCode();
        }

        turnitintooltwo_activitylog($errorstr, "API_ERROR");
        if ($toscreen) {
            turnitintooltwo_print_error($errorstr, null);
        }
    }

    /**
     * Outputs a language code to use with the Turnitin API
     *
     * @param string $langcode The Moodle language code
     * @return string The cleaned and mapped associated Turnitin lang code
     */
    private function get_lang() {
        $langcode = str_replace("_utf8", "", current_language());
        $langarray = array(
            'en' => 'en_us',
            'en_us' => 'en_us',
            'fr' => 'fr',
            'fr_ca' => 'fr',
            'es' => 'es',
            'de' => 'de',
            'de_du' => 'de',
            'zh_cn' => 'cn',
            'zh_tw' => 'zh_tw',
            'pt_br' => 'pt_br',
            'th' => 'th',
            'ja' => 'ja',
            'ko' => 'ko',
            'ms' => 'ms',
            'tr' => 'tr',
            'ca' => 'es',
            'sv' => 'sv',
            'nl' => 'nl',
            'fi' => 'fi',
            'ar' => 'ar'
        );
        $langcode = (isset($langarray[$langcode])) ? $langarray[$langcode] : 'en_us';
        return $langcode;
    }
}