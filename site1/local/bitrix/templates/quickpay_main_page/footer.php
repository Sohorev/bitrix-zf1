<footer>
    <div class="footer">
        <div class="sitetext"><span class="whitetext">Сайт разработан студией "4D проект"</span></div>
        <?
        $APPLICATION->IncludeComponent("bitrix:search.form", "bottom", array(
            "PAGE" => "#SITE_DIR#search/index.php",
            "USE_SUGGEST" => "N"
            ), false
        );
        ?>
    </div>
</footer>
<script type="text/javascript" src="<?= SITE_TEMPLATE_PATH ?>/js/script.js"></script>
</body>
</html>