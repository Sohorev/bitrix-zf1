<?

class mailer extends exchange {
    
    const STORE_PATH = BALANCE_FILE_STORE_PATH;
    
    private $localBalanceFile = '';
    
    public function __construct(){
        parent::__construct();
    }

    protected function ftpConnect()
    {
        $this->ftpHost = '188.94.225.134';
        $this->ftpPort = '21';

        if(!$this->rsFTP = ftp_connect($this->ftpHost, $this->ftpPort, $this->timeLimit)){
            $this->lastError = "Невозможно установить соединение с {$this->ftpHost}";
            //debug('no connect');
            return false;
        }
        if(!$login = ftp_login($this->rsFTP, $this->ftpUser, $this->ftpPassword)){
            $this->lastError = "Невозможно зайти на {$this->ftpHost}";
            //debug('no login');
            return false;
        }
        if (!ftp_pasv($this->rsFTP, true)) {
            $this->lastError = "Невозможно включить пассивный режим соединения";
            //debug('no pass');
            return false;
        }
        
        return true;
    }
    
    public function ftpFileGet(){
        //debug('start');
        if(!$this->ftpConnect()){
            return false;
        }
        
        $fileInfo = pathinfo($this->balanceFile);
        //debug($fileInfo);
        
        $this->localBalanceFile = P_DR . self::STORE_PATH . $fileInfo['basename'];
        
        if(!ftp_get($this->rsFTP, $this->localBalanceFile, $this->balanceFile, FTP_BINARY)){
            $this->lastError = "Не возможно скачать файл {$this->balanceFile}\n";
            //debug('! no local file');
            return false;
        }
        
        return true;
    }
    
    public function mail() {

        if(!$this->ftpFileGet()){
            $this->addLog($this->lastError);
            return false;
        }
        
        //debug('success');
    
        $arFilter = array("ACTIVE" => "Y", "UF_GET_BALANCES" => true);
        $rsUsers = CUser::GetList(($by = "id"), ($order = "desc"), $arFilter);
        while ($arUser = $rsUsers->Fetch()) {
            $arEmails[] = $arUser["EMAIL"];
        }

        foreach ($arEmails as $email) {
            $arEventFields = array(
                'EMAIL_TO' => $email,
                'FILE'     => $this->localBalanceFile
            );

            CEvent::Send(MAILING_BALANCES, array(SS_SITE_ID), $arEventFields);
        }
        
        return true;
    }
    
}

?>