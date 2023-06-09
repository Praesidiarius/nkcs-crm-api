<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class SecurityTools
{
    public function __construct(
        private readonly string $licenseAllowedClients
    ) {
    }

    private function xssCheck(array $aValsToCheck = []): bool
    {
        $aBlacklist = ['script>','src=','<script','</sc','=//'];
        foreach($aValsToCheck as $sVal) {
            foreach($aBlacklist as $sBlack) {
                $bHasBlack = stripos(strtolower($sVal),strtolower($sBlack));
                if($bHasBlack === false) {
                    # all good
                } else {
                    # found blacklisted needle in string
                    return $sBlack;
                }
            }
        }

        return false;
    }

    private function snifferCheck(array $aValsToCheck = []): bool
    {
        $aBlacklist = ['http:','__import__',
            '.popen(','gethostbyname','localtime()','form-data',
            'java.lang','/bin/bash','cmd.exe','org.apache.commons','nginx','?xml','version=',
            'ping -n','WAITFOR DELAY','../','varchar(','exec(','%2F..','..%2F','multipart/','whoami','sudo','su root','\||'];
        foreach($aValsToCheck as $sVal) {
            foreach($aBlacklist as $sBlack) {
                $bHasBlack = stripos(strtolower($sVal),strtolower($sBlack));
                if($bHasBlack === false) {
                    # all good
                } else {
                    # found blacklisted needle in string
                    return $sBlack;
                }
            }
        }

        return false;
    }

    private function sqlinjectCheck(array $aValsToCheck = []): bool
    {
        $aBlacklist = ['dblink_connect','user=','(SELECT','SELECT (','select *','union all','and 1','1=1','2=2','1 = 1', '2 = 2'];
        foreach($aValsToCheck as $sVal) {
            foreach($aBlacklist as $sBlack) {
                $bHasBlack = stripos(strtolower($sVal),strtolower($sBlack));
                if($bHasBlack === false) {
                    # all good
                } else {
                    # found blacklisted needle in string
                    return $sBlack;
                }
            }
        }

        return false;
    }

    public function basicInputCheck(array $aValsToCheck = []): string
    {
        $xssCheck = $this->xssCheck($aValsToCheck);
        if($xssCheck !== false) {
            return 'xss - '.$xssCheck;
        }

        $snifCheck = $this->snifferCheck($aValsToCheck);
        if($snifCheck !== false) {
            return 'sniff - '.$snifCheck;
        }

        $sqlCheck = $this->sqlinjectCheck($aValsToCheck);
        if($sqlCheck !== false) {
            return 'sqlinject - '.$sqlCheck;
        }

        return 'ok';
    }

    public function checkIpRestrictedAccess(Request $request): bool
    {
        $ipWhiteList = $this->licenseAllowedClients;
        $ipWhiteList = explode(',', $ipWhiteList);
        $wthIp = $request->server->get('REMOTE_ADDR');
        $secResult = $this->basicInputCheck([$wthIp]);
        if($secResult !== 'ok') {
            return false;
        }
        if(empty($wthIp) || strlen($wthIp) < 10) {
            return false;
        }
        if(!in_array($wthIp, $ipWhiteList)) {
            return false;
        }
        return true;
    }
}