<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #9: Написание своих активити для БП");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><?= $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания были выполнены следующие.</p>

<h5 class="mb-3">1. Подключен <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FDadata.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">класс</a> для работы с сервисом Dadata:</h5>

<h5 class="mb-3">2. Создан костюмный активити:</h5>
<ul>
    <li>Все лежит в отдельной директории <b>/local/activities/custom/dadatafindcompany/</b>:
        <ul>
            <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fotus%2Ftimeman%2Fconfig.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">config.php.</a></li>
            <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fotus%2Ftimeman%2Flang%2Fru%2Fscript.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">dadatafindcompanyactivity.</a></li>
            <li>языковые  файлы расположены /local/activities/custom/dadatafindcompany/lang/ru/</li>
        </ul>
    </li>
</ul>

<h5 class="mb-3">3. Создан новый инфоблок <a href="/services/lists/22/view/0/?list_section_id=">Сделка.</a>:</h5>
<ul>
     <ul>
        <li>Создан шаблон бизнес-процесса с костюмным активити.</li>
        <li>Срабатывает автоматически при создании сделки.</li>
    </ul>
</ul>

</div>



<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>