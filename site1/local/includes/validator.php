<?php

class Validator
{
    /**
     * Input fields array
     *
     * @var array $fields
     */
    public $fields = array();

    /**
     * Array of validation rules
     *
     * @var array $rules
     */
    public $rules = array();

    /**
     * Array of validation error messages
     *
     * @var array $messages
     */
    public $messages = array();

    /**
     * Returns array of validation exams
     *
     * @return array $arAnswer
     */
    public function validate ()
    {
        $arRules = $this->rules;
        foreach ($arRules as $fieldName => $fieldRules) {
            $fieldValue = $this->fields[$fieldName];
            $arValidators = $arRules[$fieldName];
            foreach ($arValidators as $validator) {
                $arValidator = explode(', ', $validator);
                $arAnswer[$fieldName][$validator] = $this->$arValidator[0]($fieldName, $fieldValue, $arValidator[1]);
            }
        }

        $arAnswer = $this->answer($arAnswer);
        return $arAnswer;
    }

    /**
     * Returns array of display validation errors
     *
     * @param array $arFields
     * @return array $displayErrors
     */
    private function answer ($arFields) {
        foreach ($arFields as $fieldName => $field) {
            $displayErrors[$fieldName] = $this->getFirstError($field);
        }

        return $displayErrors;
    }

    /**
     * Returns array of first validation errors
     *
     * @param string $field
     * @return boolean
     */
    private function getFirstError ($field) {
        foreach ($field as $error) {
            if ($error) {
                return $error;
            }
        }

        return false;
    }

    /**
     * Validation rule for required fields
     *
     * @param string $name
     * @param $val
     * @return string|boolean
     */
    private function required ($name, $val)
    {
        $errorMessage = "Это поле обязательно к заполнению";
        if (!strval($val) || strval($val) == '' || !str_replace(' ', '', $val)) {
            return ($this->messages[$name]["required"] && $this->messages[$name]["required"] != '') ? $this->messages[$name]["required"] : $errorMessage;
        }

        return false;
    }

    /**
     * Validation rule for minimum string length
     *
     * @param string $name
     * @param $val
     * @param int $min
     * @return boolean
     */
    private function minStrlen ($name, $val, $min)
    {
        if (!$val) return false;

        $errorMessage = "Минимум $min символов";
        if (mb_strlen(strval($val)) < $min) {
            return ($this->messages[$name]["minStrlen"] && $this->messages[$name]["minStrlen"] != '') ? $this->messages[$name]["minStrlen"] : $errorMessage;
        }

        return false;
    }

    /**
     * Validation rule for maximum string length
     *
     * @param string $name
     * @param $val
     * @param int $max
     * @return boolean
     */
    private function maxStrlen ($name, $val, $max)
    {
        $errorMessage = "Максимум $max символов";
        if (mb_strlen(strval($val)) > $max) {
            return ($this->messages[$name]["maxStrlen"] && $this->messages[$name]["maxStrlen"] != '') ? $this->messages[$name]["maxStrlen"] : $errorMessage;
        }

        return false;
    }

    /**
     * Validation rule for email fields
     *
     * @param string $name
     * @param $val
     * @return boolean
     */
    private function email ($name, $val)
    {
        if (!$val) return false;

        $errorMessage = "Некорректный e-mail адрес";
        if (!preg_match("/^[a-zA-Z0-9]+(([a-zA-Z0-9_.-]+)?)@[a-zA-Z0-9+](([a-zA-Z0-9_.-]+)?)+\.+[a-zA-Z]{2,4}$/", $val)) {
            return ($this->messages[$name]["email"] && $this->messages[$name]["email"] != '') ? $this->messages[$name]["email"] : $errorMessage;
        }

        return false;
    }

    /**
     * Validation rule for numeric fields
     *
     * @param string|integer $name
     * @param $val
     * @return boolean
     */
    private function num ($name, $val)
    {
        if (!$val) return false;

        $errorMessage = "Допустимые символы: только цифры";
        if (!is_numeric($val)) {
            return ($this->messages[$name]["num"] && $this->messages[$name]["num"] != '') ? $this->messages[$name]["num"] : $errorMessage;
        }

        return false;
    }

    private function userExists ($name, $val)
    {
        if (!$val) return false;

        $errorMessage = "Неверный логин";
        if (!UserHelper::CheckExistsByLogin($val)) {
            return ($this->messages[$name]["userExists"] && $this->messages[$name]["userExists"] != '') ? $this->messages[$name]["userExists"] : $errorMessage;
        }

        return false;
    }

    private function userNotExists ($name, $val)
    {
        if (!$val) return false;

        $errorMessage = "Данный логин уже занят";
        if (UserHelper::CheckExistsByLogin($val)) {
            return ($this->messages[$name]["userNotExists"] && $this->messages[$name]["userNotExists"] != '') ? $this->messages[$name]["userNotExists"] : $errorMessage;
        }

        return false;
    }

    private function userActive ($name, $val)
    {
        if (!$val) return false;

        $errorMessage = "Ваш аккаунт еще не активирован.";
        if (!UserHelper::CheckActiveByLogin($val)) {
            return ($this->messages[$name]["userExists"] && $this->messages[$name]["userExists"] != '') ? $this->messages[$name]["userExists"] : $errorMessage;
        }

        return false;
    }

    /**
     * Validation rule for phone fields
     *
     * @param string $value
     * @param $val
     * @return string|boolean
     */
    private function phone ($name, $val)
    {
        if (!$val) return false;

        return false;
    }

    private function uniqSubscribe ($name, $val)
    {
        $errorMessage = "Такой адрес подписки уже существует";
        if (UserHelper::CheckSubscriptionExistByEmail($val)) {
            return ($this->messages[$name]["uniqSubscribe"] && $this->messages[$name]["uniqSubscribe"] != '') ? $this->messages[$name]["uniqSubscribe"] : $errorMessage;
        }

        return false;
    }
}