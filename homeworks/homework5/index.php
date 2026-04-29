<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle(""); ?><?php
$APPLICATION->SetTitle("ДЗ #5: Компонент списка таблицы БД ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>
<h4 class="mb-3">Пояснительная записка</h4>
<div>
	<p>
		 При реализации домашнего задания было выполнены следующие цели.
	</p>
	<h5 class="mb-3">1. Создана собственая <a href="/bitrix/admin/fileman_admin.php?lang=ru&path=%2Flocal%2Fcomponents%2Fotus%2FCurrencyRate&site=s1">компонента</a>, выводящая курс валюты при выборе из выподающего списка:</h5>
	<ul>
		<li>Для реализации компоненты созданны следующие файлы:
		<ul>
			<li>Создан файл <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2FCurrencyRate%2Fclass.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">class.php</a>.</li>
			<li>Создан файл <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2FCurrencyRate%2F.description.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">.description.php</a>.</li>
			<li>Создан файл <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2FCurrencyRate%2F.parameters.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">.parameters.php</a>.</li>
			<li>Создан файл шаблона <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fcomponents%2Fotus%2FCurrencyRate%2Ftemplates%2F.default%2Ftemplate.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">template.php</a>.</li>
		</ul>
 </li>
	</ul>
	<h5 class="mb-3">2. Вывод компоненты на странцу:</h5>
</div>
 <?$APPLICATION->IncludeComponent(
	"otus:CurrencyRate",
	"",
Array()
);?><br><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>