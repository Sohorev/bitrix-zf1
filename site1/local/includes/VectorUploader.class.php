<?php

/**
 * Класс загрузчика файла
 */
class VectorUploader
{
    /**
     * Массив, содержащий описание загруженного файла
     * @var array
     */
    public $file = array();

    /**
     * Название файла для сохранения в сессию и записи в БД
     * @var string
     */
    public $fileName = '';

    /**
     * Название файла для отображения пользователю
     * @var string
     */
    public $origFileName = '';

    /**
     * Директория для загрузки локальных файлов
     * @var string
     */
    public $uploadDir = '';

    /**
     * ID текущего пользователя
     * @var int
     */
    public $userId = 1;

    /** 
     * Ошибка
     * @var boolean  
     */
    public $error = false;

    /**
     * Описание возникшей ошибки или успеха скрипта
     * @var string
     */
    public $message = 'Файл успешно загружен на FTP';

    /**
     * Ответ сервера
     * @var array
     */
    public $answer = array();

    /**
     * Ограничения на расширения загружаемых файлов
     * @var array
     */
    protected $extensions = array('cdr', 'eps', 'pdf', 'ai', 'jpg', 'jpeg');

    /**
     * Ограничение на размер загружаемых файлов
     * @var int
     */
    protected $maxFileSize = 15728640;

    /**
     * Параметры подключения к FTP для выкладки векторных макетов
     * @var array
     */
    public $ftp = array(
        "SERVER" => VECTOR_FTP_HOST,
        "PORT"   => VECTOR_FTP_PORT,
        "USER"   => VECTOR_FTP_USER,
        "PASS"   => VECTOR_FTP_PASS
    );

    /**
     * Конструктор класса. Сохраняет в свойства объекта файл и ID текущего пользователя.
     * @param array  $file
     * @param string $uploadDir
     * @param int    $userId
     */
    public function __construct($file, $uploadDir, $userId)
    {
        $this->file = $file;
        $this->origFileName = $this->file["name"][0];
        $this->uploadDir = $uploadDir;
        $this->userId = $userId;
    }

    /**
     * Скрипт последовательной загрузки файла, сохранения его локально и отправки по FTP.
     * @param $idApp
     * @return object $this
     */
    public function start($idApp = null)
    {
        if($idApp)
            $this->checkFile()->checkUploadDir()->saveLocalFile()->uploadToFTP()->saveToSession($idApp)->setAnswer();
        else
            $this->checkFile()->checkUploadDir()->saveLocalFile()->uploadToFTP()->saveToSession()->setAnswer();
        return $this;
    }

    /**
     * Проверяет файл на соответствие ограничениям.
     * @return object $this
     */
    public function checkFile()
    {
        $ext = $this->getFileExtension($this->origFileName);
        if (! in_array($ext, $this->extensions)) {
            $this->error = true;
            $this->message = 'Загружаемый файл имеет недопустимое расширение';
        }

        $fileSize = $this->file["size"];
        if (is_array($fileSize)) {
            $fileSize = $fileSize[0];
        }

        if ($fileSize > $this->maxFileSize) {
            $this->error = true;
            $this->message = 'Загружаемый файл больше максимально разрешенного размера';
        }
        
        return $this;
    }

    /**
     * Создает в случае необходимости директорию для сохранения локальных фалов
     * @return object $this
     */
    public function checkUploadDir()
    {
        if (! $this->error) {
            if (! file_exists($this->uploadDir) || ! is_dir($this->uploadDir)) {
                if (! mkdir($this->uploadDir)) {
                    $this->error = true;
                    $this->message = 'Не удалось создать директорию для загрузки векторных макетов';
                }
            }
        }

        return $this;
    }

    /**
     * Сохраняет файл в папку на локальном сервере
     * @return object $this
     */
    public function saveLocalFile()
    {
        if (! $this->error) {
            $this->fileName = $this->getFileName();
            if (! move_uploaded_file($this->file["tmp_name"][0], $this->uploadDir . $this->fileName)) {
                $this->error = true;
                $this->message = 'Не удалось сохранить файл локально';
            }
        }

        return $this;
    }

    /**
     * Возвращает сгенерированное имя для файла
     * @return string
     */
    public function getFileName()
    {
        $ext = $this->getFileExtension($this->file["name"][0]);
        return $this->getRandFileName() . '.' . $ext;
    }

    /**
     * Возвращает случайную строку для имени файла
     * @return string
     */
    public function getRandFileName()
    {
        return date('YmdHis') . randString(6);
    }

    /**
     * Возвращает расширение файла
     * @param  string $str
     * @return string
     */
    public function getFileExtension($str)
    {
        return strtolower(substr(strrchr($str, '.'), 1));
    }

    /**
     * Загружает сохраненный файл на FTP
     * @return object $this
     */
    public function uploadToFTP()
    {
        if (! $this->error) {
            CModule::IncludeModule('iblock');
            $arFilter = array('IBLOCK_ID' => IB_SETTINGS, 'ACTIVE' => 'Y', 'CODE' => VECTOR_FTP_FOLDER);
            $arFolder = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID", "PROPERTY_TEXT"))->Fetch();

            $file = $this->uploadDir . $this->fileName;

            // Проверяем наличие папки для загрузки файлов в админке
            if (! $folder = $arFolder["PROPERTY_TEXT_VALUE"]) {
				$folder = "/";
			}
			// Соединяемся с FTP
			if (! $ftpConn = ftp_connect($this->ftp["SERVER"], $this->ftp["PORT"])) {
				$this->error = true;
				$this->message = 'Не удалось соединиться с FTP для загрузки макетов';
			} else {
				// Авторизуемся на ftp
				if (! @ftp_login($ftpConn, $this->ftp["USER"], $this->ftp["PASS"])) {
					$this->error = true;
					$this->message = 'Не удалось авторизоваться на FTP для загрузки макетов';
				} else {
					if ($folder != "/") {
						$arFolders = explode('/', $folder);
						foreach ($arFolders as $curFolder) {
							// Заходим в нужную директорию
							if (! ftp_chdir($ftpConn, $curFolder)) {
								// Создаем, если нет
								if (! ftp_mkdir($ftpConn, $curFolder)) {
									$this->error = true;
									$this->message = 'Не удалось создать папку "'. $curFolder .'" на FTP';
								} else {
									ftp_chdir($ftpConn, $curFolder);
								}
							}
						}
					} else {
						ftp_chdir($ftpConn, $folder);
					}

					// Если все ок, закачиваем файл на сервер
					if (! $this->error) {
						if (! ftp_put($ftpConn, $this->fileName, $file, FTP_BINARY)) {
							$this->error = true;
							$this->message = $this->message = "Не удалось загрузить файл на FTP";
						}
					}
				}
			}

			ftp_close($ftpConn);
        }

        return $this;
    }

    /**
     * Добавляет загруженный файл в сессию пользователя на случай, если он не оформит заказ сразу
     * @param $idApp
     * @return object $this
     */
    public function saveToSession($idApp = null)
    {
        if (! $this->error) {
            if(!$idApp)
                $_SESSION["USER_FILES"][$this->fileName] = $this->origFileName;
            else
                $_SESSION["USER_FILES_".$idApp][$this->fileName] = $this->origFileName;
        }

        return $this;
    }

    /**
     * Подготавливает ответ от сервера
     * @return object $this
     */
    public function setAnswer()
    {
        $this->answer = array(
            "ERROR"   => $this->error,
            "MESSAGE" => $this->message,
            "FILE"    => array(
                "NAME_ORIG" => $this->origFileName,
                "NAME"      => $this->fileName
            )
        );

        return $this;
    }

    /**
     * Удаляет товар по имени из сессии
     * @param  string $fileName
     * @return object $this
     */
    public function deleteFormSession($fileName)
    {
        unset($_SESSION["USER_FILES"][$this->fileName]);
        return $this;
    }

    /**
     * Очистка товаров из сессии
     * @return object $this
     */
    public function clearSession()
    {
        unset($_SESSION["USER_FILES"]);
        return $this;
    }
}