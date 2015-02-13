<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
IncludeTemplateLangFile(__FILE__);
?>
<!DOCTYPE>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE_ID ?>" lang="<?= LANGUAGE_ID ?>">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="viewport" content="width=1024" />
        <link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <title><? $APPLICATION->ShowTitle() ?></title>
        <?
        $APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . "/css/style.css");
        $APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH . "/js/jquery-1.11.2.min.js");
        $APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH . "/js/jquery-migrate-1.2.1.min.js");
        $APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH . "/js/initForm.min.js");
        $APPLICATION->ShowHead();
        ?>
    </head>
    <body>
        <? $APPLICATION->ShowPanel(); ?>
        <!--[if lt IE 9]>
            <script>
                document.createElement('header');
                document.createElement('nav');
                document.createElement('section');
                document.createElement('article');
                document.createElement('aside');
                document.createElement('footer');
            </script>
            <style>
                header, nav, section, article, aside, footer {
                   display:block;
                }
            </style>
        <![endif]-->
        <header>
            <div class="logo">
                <a href="/index.php"><img src="<?= SITE_TEMPLATE_PATH ?>/images/logo.png" width="242" height="71" alt="Quickpay"/></a>
            </div>
            <div class="country">
                <a href="#">Россия</a><br/>
                <span>Выберите страну</span>
            </div>
            <div class="phonenumber">
                <?
                $APPLICATION->IncludeComponent(
                    "bitrix:main.include", ".default", array(
                    "AREA_FILE_SHOW" => "file",
                    "PATH" => SITE_DIR . "include/telephone.php",
                    "EDIT_TEMPLATE" => ""
                    ), false
                );
                ?>
            </div>
            <div class="login">
                <input type="image" src="<?= SITE_TEMPLATE_PATH ?>/images/login.png"/>
                <div class="logintext"><span class="whitetext">Вход для дилеров</span></div>
            </div>
            <div style="clear:both"></div>
            <?
            $APPLICATION->IncludeComponent(
                "bitrix:menu", "top", array(
                "ROOT_MENU_TYPE" => "top",
                "MENU_CACHE_TYPE" => "N",
                "MENU_CACHE_TIME" => "3600",
                "MENU_CACHE_USE_GROUPS" => "Y",
                "MENU_CACHE_GET_VARS" => array(
                ),
                "MAX_LEVEL" => "1",
                "CHILD_MENU_TYPE" => "top",
                "USE_EXT" => "N",
                "DELAY" => "N",
                "ALLOW_MULTI_SELECT" => "N"
                ), false
            );
            ?>
        </header>
        <div class="wave"></div>
        <div class="headtext">
            <div class="menuhrefs">
                <a href="#" class="menuhref">Заявка на тестирование ПС</a> <a href="#" class="menuhref">Заявка на подключение к ПС</a> <img alt="" src="<?= SITE_TEMPLATE_PATH ?>/images/question.png" height="19" width="19"> <a href="#" class="menuhref">Задать вопрос</a>
            </div>
        </div>