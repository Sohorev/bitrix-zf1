<?
class Helper
{
    public static function rightSuffix($count, $form1 = "", $form2_4 = "а", $form5_0 = "ов")
    {
        $n100 = $count % 100;
        $n10  = $count % 10;
        if (($n100 > 10) && ($n100 < 21)) {
            return $form5_0;
        } else if ((!$n10) || ($n10 >= 5)) {
            return $form5_0;
        } else if ($n10 == 1) {
            return $form1;
        }
        return $form2_4;
    }

    public static function monthName($num)
    {
        switch ($num) {
            case 1:  return 'Январь';   break;
            case 2:  return 'Февраль';  break;
            case 3:  return 'Март';     break;
            case 4:  return 'Апрель';   break;
            case 5:  return 'Май';      break;
            case 6:  return 'Июнь';     break;
            case 7:  return 'Июль';     break;
            case 8:  return 'Август';   break;
            case 9:  return 'Сентябрь'; break;
            case 10: return 'Октябрь';  break;
            case 11: return 'Ноябрь';   break;
            case 12: return 'Декабрь';  break;
            default: return false;
        }

    }

    public static function resetSessionFilter () {
        $arFilterSections = array("ezhednevniki", "ruchki", "aksessuary");
        $arFilterItems = array("category", "price_min", "price_max", "color", "material");
        foreach ($arFilterSections as $filterSection) {
            foreach ($arFilterItems as $filterItem) {
                unset($_SESSION[$filterSection . "_" . $filterItem]);
            }
        }
    }
}

?>