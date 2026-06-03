<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #8: Учимся подключать свои скрипты, взаимодействовать с компонентами из фронтенда");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><?= $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания были выполнены следующие.</p>

<h5 class="mb-3">1. Подключение произвольного JS-кода:</h5>
<ul>
    <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">init.php</a> — используется как диспетчер.</li>
    <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtus%2FtimeWindow.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">timeWindow.php</a> — регистрирует обработчик события <b>onEpilog</b>.</li>
</ul>

<h5 class="mb-3">2. Создание JS-расширения:</h5>
<ul>
    <li>Все лежит в отдельной директории <b>/local/js/otus/timeman/</b>:
        <ul>
            <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fotus%2Ftimeman%2Fconfig.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">config.php.</a></li>
            <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fotus%2Ftimeman%2Flang%2Fru%2Fscript.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">script.php (Языковой файл).</a></li>
            <li><a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fjs%2Fotus%2Ftimeman%2Fscript.js&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">script.js</a> — логига перехватаи и обработки выбора.</li>
        </ul>
    </li>
</ul>

<h5 class="mb-3">3. Отслеживание системных JS-событий и реакция на них:</h5>
<ul>
    <li>Внедрен перехватчик на метод <b>BX.ajax</b>, который обрабатывает виджет учета времени:
        <ul>
            <li>При нажатии «Начать рабочий день» или «Продолжить» (экшены <i>open/start/reopen/resume</i>) отправка запроса приостанавливается и вызывается модальное окно <b>BX.PopupWindow</b>.</li>
            <li>При подтверждении — выполняется оригинальный AJAX-запрос и стартует день.</li>
            <li>При закрытии окна или нажатии «Отмена» — запрос подменяется на безопасный экшен <i>status</i>, а интерфейс принудительно перерисовывается методами <b>BX.TimeMan.Grid.setItems</b> и <b>BX.TimeMan.Grid.redraw</b>.</li>
        </ul>
    </li>
</ul>

</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>