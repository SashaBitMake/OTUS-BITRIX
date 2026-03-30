<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");
$logFileName = "custom_" . date('d.m.Y') . ".log";
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<h4 class="mb-3">Пояснительная записка</h4>
<div>
<p>При реализации домашнего задания было выполнены 2 цели.</p>

<h3>Часть 1 - Logger</h3>
<ul>
    <li>Разработка кастомного класса, наследуемый из <b>"FileExceptionHandlerLog"</b>, для управления файлами логов с поддержкой операций записи и очистки.
        <ul>
            <li>1. Метод записи в лог (addLog)
                <ul>
                    <li><b>Триггер:</b> Активация при клике на ссылку.</li>
                    <li><b>Действие:</b> Создает новый файл или дополняет существующий записью.</li>
                    <li><b>Контент:</b> Фиксируется тестовое сообщение "Test".</li>
                </ul>
            </li>
            <li>2. Метод очистки лога (cleanLog)
                <ul>
                    <li><b>Действие:</b> Принудительная очистка целевого файла (усечение до 0 байт).</li>
                    <li><b>Результат:</b> Файл сохраняется, но теряет содержимое.</li>
                </ul>
            </li>
            <li>Именование файлов: Динамическая генерация.
                <ul>
                    <li><b>Шаблон:</b> custom_&lt;DD-MM-YYYY&gt; (префикс custom_ + текущая дата).</li>
                    <li><b>Формат данных:</b> Текстовый лог.</li>
                </ul>
            </li>
            <li><b>Выявил задержку синхронизации файла после очистки,</b> необходимо подождать ориентировочно минуту и скачать файл, тогда будет пуст.</li>
        </ul>
    </li>
</ul> 

<h3>Часть 2 - Exception</h3>
<ul>
    <li>В кастомный класс, наследуемый из <b>"FileExceptionHandlerLog"</b>, изменен метод <b>"write"</b>
        <ul>
            <li>1. Метод записи в лог (write)
                <ul>
                    <li><b>Триггер:</b> Активация при клике на ссылку.</li>
                    <li><b>Действие:</b> Создает новый файл или дополняет существующий записью.</li>
                    <li><b>Контент:</b> Фиксируется тестовое сообщение "OTUS" в начале строки.</li>
                </ul>
            </li>
            <li>2. Метод очистки лога (cleanLog)
                <ul>
                    <li><b>Действие:</b> Принудительная очистка целевого файла (усечение до 0 байт).</li>
                    <li><b>Результат:</b> Файл сохраняется, но теряет содержимое.</li>
                </ul>
            </li>
            <li><b>Выявил задержку синхронизации файла после очистки,</b> необходимо подождать ориентировочно минуту и скачать файл, тогда будет пуст.</li>
        </ul>
    </li>
</ul>
    
</div>
<br>
<br>
<hr>

<h4 class="mb-3">Часть 1 - Logger</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/<?= $logFileName ?>">Файл лога из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writelog.php">Добавление в лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="clearlog.php">Очистить лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FDebug%2FLog.php&full_src=Y">Файл с классом кастомного логгера</a>
    </li>
</ul>


<h4 class="mb-3 mt-5">Часть 2 - Exception</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/exceptions.log">Файл лога из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writeexception.php">Добавление в лог из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="clearexception.php">Очистить лог из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FDebug%2FLog.php&full_src=Y">Файл с классом системного исключений</a>
    </li>
</ul>



<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>