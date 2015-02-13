<?
class usersExport extends export 
{
    public function export()
    {
        return ($this->exportContractors() && 
                $this->exportUsers());
    }
    
    private function exportUsers() 
    {
        $arFilter = array('ACTIVE' => 'Y', 'UF_CST' => True);
        
        $arSelect = array("SELECT"=>array("UF_CONTRACTOR", "UF_USER_ID", "UF_SENDED_FILE"));
        
        $rsUsers = CUser::GetList($by="timestamp_x", $order="desc", $arFilter, $arSelect);
    
        while($row = $rsUsers->Fetch()){
            $arFields = array(
                'CodeSt'    => $row['ID'],
                'CSt'       => 'True',
                'Name1'     => $row['LAST_NAME'],
                'Name2'     => $row['NAME'],
                'Name3'     => $row['SECOND_NAME'],
                'Phone'     => $row['WORK_PHONE'],
                'Mail'      => $row['EMAIL'],
                'StId'      => self::SITE_ID
            );
            if($this->personExists($row['ID'])){
                if(!$this->updatePerson($row['ID'], $arFields)){
                    $this->addLog($this->lastError);
                }
                // Updating Person and Contractors connections
                /* Выбираем из буферной базы текущие привязки для пользователя */
                $result = $this->selectContractorPerson($row['ID']);
                $curContractorIds = array();

                /* Удаляем из буферной базы привязки которых нет на сайте */
                if (!$row['UF_SENDED_FILE']) {
                    while ($arContPerson = mssql_fetch_assoc($result)) {
                        $contractorId = trim($this->toUnicode($arContPerson['ContactorSt']));
                        $curContractorIds[] = $contractorId;
                        if (!in_array($contractorId, $row['UF_CONTRACTOR'])) {
                            $values = array(
                                'ContactorSt'  => $contractorId,
                                'PersonSt'      => $row['ID']
                            );
                            // Удаление устаревшей записи
                            $message = "Ошибка удаления связи контрагента и пользователя PersonSt={$row['ID']}, ContactorSt={$contractorId}";
                            if (!$this->doDelete(self::IMPORT_CONTPERSONS_TABLE, $values, $message)) {
                                $errors[$contractorId] = $this->lastError;
                            } else {
                                /* Если не удалось удалить то запишем привязку в текущие */
                                $curContractorIds[] = $contractorId;
                            }
                        } else {
                            /* Формируем массив текущих контрагентов */
                            $curContractorIds[] = $contractorId;
                        }
                    }

                    /* Добавляем в буферную базу привязки, которых там нет */
                    foreach($row['UF_CONTRACTOR'] as $userContractor) {
                        if (!in_array($userContractor, $curContractorIds)) {
                            /* Если в буферной базе не привязан данный контрагент - привязываем */
                            $values = array(
                                'Contactor'     => $this->getContractor1CCode($userContractor),
                                'Person'        => $row['UF_USER_ID'],
                                'ContactorSt'   => $userContractor,
                                'PersonSt'      => $row['ID'],
                                'StId'          => self::SITE_ID
                            );
                            $this->insertContractorPerson($values);
                        }
                    }
                }
            } else {
                if(!$this->insertPerson($arFields)){
                    $this->addLog($this->lastError);
                }
                // Adding fake Contractor
                $arFields = array(
                    'CodeSt'        => $row['ID'],
                    'CSt'           => 'True',
                    'Name'          => $row['WORK_COMPANY']
                );
                $this->insertContractor($arFields);
                // Adding Person and Contractors connections
                $values = array(
                    'ContactorSt'   => $row['ID'],
                    'PersonSt'      => $row['ID'],
                    'StId'          => self::SITE_ID
                );
                $this->insertContractorPerson($values);
            }
            $this->uncheckPerson($row['ID']);
        }
        
        return true;
    }
    
    private function exportContractors() 
    {
        $arSelect = array("ID", "NAME", "PROPERTY_INN", "PROPERTY_CITY", "PROPERTY_MANAGER", "PROPERTY_RESERV_LIMIT");
        $arFilter = array("IBLOCK_ID"=>self::IB_CONTRACTORS, "ACTIVE"=>"Y", "PROPERTY_CSt"=>CONTRACTOR_CHANGED);
        $res = CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
        while ($row = $res->GetNext()) {
            $arFields = array(
                'CodeSt'        => $row['ID'],
                'CSt'           => 'True',
                'Name'          => $row['NAME'],
                "StId"          => self::SITE_ID
            );

            if ($this->contractorExists($row['ID'])) {
                if (!$this->updateContractor($row['ID'], $arFields)) {
                    $this->addLog($this->lastError);
                }
            } else {
                if (!$this->insertContractor($arFields)) {
                    $this->addLog($this->lastError);
                }
            }

            $this->uncheckContractor($row['ID']);       
        }
        
        return true;
    }
    
    private function getContractor1CCode($contractorId) 
    {
        $arSelect = array("ID", "XML_ID");
        $arFilter = array("IBLOCK_ID"=>self::IB_CONTRACTORS, "ACTIVE"=>"Y", "ID"=>$contractorId);
        $res = CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
        $arContractor = $res->GetNext();
        return $arContractor['XML_ID'];
    }
    
    protected function uncheckContractor($codeSt)
    {
        CIBlockElement::SetPropertyValuesEx(
            $codeSt,
            self::IB_CONTRACTORS,
            array("CSt" => false)
        );
    
        return true;
    }
    
    protected function uncheckPerson($codeSt)
    {
        $arFields = array("UF_CST" => False);
    
        $user = new CUser();
        if(!$user->Update($codeSt, $arFields)){
            return false;
        }
    
        return true;
    }
    
    public function __destruct()
    {
        /*if (file_exists($this->logFile)) {
             $this->emailLog('Ошибки при отправке пользователей в 1С');
        }*/
    }

    /* ------------------------------------------------------------------------------------------
        @PUBLIC
    ------------------------------------------------------------------------------------------ */

    /**
     * addNewPerson
     * Добавляет нового пользователя, контрагента и привязывает их друг у другу.
     * 
     * @param int    $userId         [Идентификатор нового пользователя]
     * @param string $contractorName [Наименование контрагента организации]
     * @return bool
     */
    public function addNewPerson($userId, $contractorName = null)
    {
        // Пользователь обязателен
        if (! (intval($userId))) {
            return false;
        }

        // Достаем все данные, указанные пользователем при регистрации
        $userData = $this->getPerson($userId);

        if (! $contractorName) {
            $contractorName = "не указано";
        }

        // Добавляем нового пользователя
        if (! $this->personExists($userId)) {
            if (! $this->insertPerson(array(
                "CodeSt" => $userId,
                "CSt"    => "True",
                "Name1"  => $userData["LAST_NAME"],
                "Name2"  => $userData["NAME"],
                "Phone"  => $userData["WORK_PHONE"],
                "Mail"   => $userData["EMAIL"],
                "StId"   => self::SITE_ID
            ))) {
                return false;
            }
        } else {
            if (! $this->updatePerson($userId, array(
                "CSt" => "True"
            ))) {
                return false;
            }
        }

        // Добавляем контрагента на сайт, но пока не активируем.
        if (! $newContractorId = $this->addContractorToSite(array(
            "IBLOCK_ID" => self::IB_CONTRACTORS,
            "ACTIVE" => "N",
            "NAME" => $contractorName,
            "PROPERTY_VALUES" => array(
                "CITY" => $userData["WORK_CITY"]
            )
        ))) {
            return false;
        }

        // Добавляем контрагента в БД
        if (! $this->insertContractor(array(
            "CodeSt" => $newContractorId,
            "CSt"    => "True",
            "Name"   => $contractorName,
            "City"   => $userData["WORK_CITY"],
            "StId"   => self::SITE_ID
        ))) {
            return false;
        }

        // Привязываем добавленного контрагента к пользователю
        if (! $this->contractorPersonExists($newContractorId, $userId)) {
            if (! $this->insertContractorPerson(array(
                "ContactorSt" => $newContractorId,
                "PersonSt"    => $userId,
                "StId"        => self::SITE_ID
            ))) {
                return false;
            }
        }

        return true;
    }

    /**
     * addNewContractor
     * Добавляет нового контрагента и привязывает его к указанному пользователю.
     * 
     * @param int    $userId         [Идентификатор пользователя, к которому необходимо привязать контрагента]
     * @param string $contractorName [Наименование контрагента организации]
     * @return bool
     */
    public function addNewContractor($userId, $contractorName = null)
    {
        // Пользователь обязателен
        if (! (intval($userId))) {
            return false;
        }

        // Если наименование контрагента не указано, генерируем сами
        $contractorName = $contractorName 
            ? $contractorName 
            : "Новый контрагент пользователя {$userId}";

        // Получаем город, который пользователь указал при регистрации.
        // Этот город по умолчанию укажем для нового контрагента.
        $arUser = CUser::GetByID($userId)->Fetch();
        $city = $arUser["WORK_CITY"];

        // Добавляем контрагента на сайт, но пока не активируем.
        if (! $newContractorId = $this->addContractorToSite(array(
            "IBLOCK_ID" => self::IB_CONTRACTORS,
            "ACTIVE" => "N",
            "NAME" => $contractorName,
            "PROPERTY_VALUES" => array(
                "CITY" => $city
            )
        ))) {
            return false;
        }

        // Добавляем контрагента в БД
        if (! $this->insertContractor(array(
            "CodeSt" => $newContractorId,
            "CSt"    => "True",
            "Name"   => $contractorName,
            "City"   => $city,
            "StId"   => self::SITE_ID
        ))) {
            return false;
        }

        // Привязываем добавленного контрагента к пользователю
        if (! $this->contractorPersonExists($newContractorId, $userId)) {
            if (! $this->insertContractorPerson(array(
                "ContactorSt" => $newContractorId,
                "PersonSt"    => $userId,
                "StId"        => self::SITE_ID
            ))) {
                return false;
            }
        }

        // Помечаем пользователя как измененного 1С
        if (! $this->updatePerson($userId, array(
            "CSt" => "True"
        ))) {
            return false;
        }

        return true;
    }

    /* ------------------------------------------------------------------------------------------
        @IB: OPERATIONS
    ------------------------------------------------------------------------------------------ */

    protected function getPerson($id)
    {
        $arUser = CUser::GetList(
            $by = "id",
            $order = "asc",
            array("ID" => $id)
        )->GetNext();

        return $arUser ? $arUser : false;
    }

    protected function addContractorToSite($fields)
    {
        if (! CModule::IncludeModule('iblock')) {
            return false;
        }

        $iblock = new CIBlockElement();
        return $iblock->Add($fields);
    }

    /* ------------------------------------------------------------------------------------------
        @DB: OPERATIONS
    ------------------------------------------------------------------------------------------ */

    /**
     * insertContractor
     * Добавялет новую запись в таблицу контрагентов
     * 
     * @param  array $values [Массив вида "название поля" => "значение"]
     * @return bool          [Результат выполнения запроса]
     */
    protected function insertContractor($values)
    {
        // Валидация входных параметров
        if (! $values["CodeSt"] || ! $values["StId"]) {
            $this->lastError = "Не все поля заполнены";
            return false;
        }
        
        // Если в сессии есть файл с карточкой клиента, его название тоже пишем в таблицу
        if (! empty($_SESSION["CARD_FILE"]["FILE_NAME"]) && $_SESSION["CARD_FILE"]["FILE_NAME"] != "") {
            $values["FileData"] = $_SESSION["CARD_FILE"]["FILE_NAME"];
        }

        // Добавление
        return $this->doInsert(
            self::IMPORT_CONTACTORS_TABLE, 
            $values,
            "Ошибка добавления нового контрагента CodeSt={$values["CodeSt"]}"
        );
    }

    /**
     * updateContractor
     * Обновляет запись в таблице контрагентов
     *
     * @param  int $contractorId    [Идентификатор контрагента]
     * @param $values
     * @internal param array $vales [Массив вида "название поля" => "значение"]
     * @return bool                 [Результат выполнения запроса]
     */
    protected function updateContractor($contractorId, $values)
    {
        // Обновление
        return $this->doUpdate(
            self::IMPORT_CONTACTORS_TABLE,
            $values,
            array(
                "CodeSt" => $contractorId
            )
        );
    }

    /**
     * insertContractorPerson
     * Добавялет новую запись в таблицу связей контрагентов и пользователей
     * 
     * @param  array $values [Массив вида "название поля" => "значение"]
     * @return bool          [Результат выполнения запроса]
     */
    protected function insertContractorPerson($values)
    {
        // Валидация входных параметров
        if (! $values["ContactorSt"] || ! $values["PersonSt"] || ! $values["StId"]) {
            $this->lastError = "Не все поля заполнены";
            return false;
        }

        // Добавление
        return $this->doInsert(
            self::IMPORT_CONTPERSONS_TABLE, 
            $values,
            "Ошибка добавления связи контрагента и пользователя CodeSt={$values["CodeSt"]}"
        );
    }

    protected function selectContractorPerson($userId = false) {
        // Валидация входных параметров
        if (! $userId) {
            $this->lastError = "Не все поля заполнены";
            return false;
        }

        $values = array(
            'PersonSt' => $userId
        );
        // Select
        return $this->doSelect(
            self::IMPORT_CONTPERSONS_TABLE,
            $values,
            null,
            "Ошибка выборки данных PersonSt={$userId}"
        );
    }

    protected function deleteContractorPerson($userId = false)
    {
        // Валидация входных параметров
        if (! $userId) {
            $this->lastError = "Не все поля заполнены";
            return false;
        }

        $values = array(
            'PersonSt' => $userId
        );
        // Добавление
        return $this->doDelete(
            self::IMPORT_CONTPERSONS_TABLE, 
            $values,
            "Ошибка удаления связи контрагента и пользователя PersonSt={$userId}"
        );
    }

    /**
     * insertPerson
     * Добавляет новую запись в таблицу пользователей
     *
     * @param $values
     * @internal param array $vales [Массив вида "название поля" => "значение"]
     * @return bool                 [Результат выполнения запроса]
     */
    protected function insertPerson($values)
    {
        // Добавление
        return $this->doInsert(
            self::IMPORT_PERSONS_TABLE, 
            $values,
            "Ошибка добавления пользователя CodeSt={$values["CodeSt"]}"
        );
    }

    /**
     * updatePerson
     * Обновляет запись в таблице пользователей
     *
     * @param  int $userId          [Идентификатор пользователя]
     * @param $values
     * @internal param array $vales [Массив вида "название поля" => "значение"]
     * @return bool                 [Результат выполнения запроса]
     */
    protected function updatePerson($userId, $values)
    {
        // Обновление
        return $this->doUpdate(
            self::IMPORT_PERSONS_TABLE,
            $values,
            array(
                "CodeSt" => $userId
            )
        );
    }

    /**
     * personExists
     * Проверяет существование пользователя в БД
     * 
     * @param  int $id [Идентификатор пользователя на сайте]
     * @return bool    [Существует или нет]
     */
    protected function personExists($id)
    {
        return $this->existsInDB(self::IMPORT_PERSONS_TABLE, array(
            "CodeSt" => $id
        ));
    }

    /**
     * contractorExists
     * Проверяет существование контрагента в БД
     * 
     * @param  int $id [Идентификатор контрагента на сайте]
     * @return bool    [Существует или нет]
     */
    protected function contractorExists($id)
    {
        return $this->existsInDB(self::IMPORT_CONTACTORS_TABLE, array(
            "CodeSt" => $id
        ));
    }

    /**
     * contractorPersonExists
     * Проверяет существование связи между контрагентом и пользователем
     * 
     * @param  int $contractorId [Идентификатор контрагента на сайте]
     * @param  int $personId     [Идентификатор пользователя на сайте]
     * @return bool              [Существует или нет]
     */
    protected function contractorPersonExists($contractorId, $personId)
    {
        return $this->existsInDB(self::IMPORT_CONTPERSONS_TABLE, array(
            "ContactorSt" => $contractorId,
            "PersonSt"    => $personId
        ));
    }
}