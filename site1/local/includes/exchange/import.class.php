<?

abstract class import extends exchange {

    // максимальное количество возможных цветов, все, что сверх должно игнорироваться с генерацией ошибки
    const MAX_COLORS = 16;
    // максимальная длинна генерируемого налету мнемонического кода
    const CODE_MAX_LENGTH = 150;

    public function __construct(){
        parent::__construct();
    }
    
    // функция непосредственно осуществляющая импорт
    abstract public function import();
   
}

?>