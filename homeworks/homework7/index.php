<?

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle(""); ?><?php
$APPLICATION->SetTitle("ДЗ #7: Создание кастомных полей и встраивание их в систему - в процессе");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');


?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>
<h4 class="mb-3">Пояснительная записка</h4>
<div>
	<p>
		 При реализации домашнего задания было выполнены следующие цели.
	</p>
	<h5 class="mb-3">1. Создана список <a href="/services/lists/21/view/0/?list_section_id=">Бронирования</a>, <a href="/bitrix/admin/iblock_edit.php?type=lists&lang=ru&ID=21&admin=Y">свойство</a> списка в административной части.</h5>
	<h5 class="mb-3">2. Добавлено <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2FApp%2FIblock%2FProperties%2FDoctorBookingProperty.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">кастомное свойство</a>, для списка <a href="/services/lists/16/view/0/?list_section_id=">врачей</a>, подключен через <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">init.php</a>:</h5>
    <ul>
		<li>При помощи свойства реализовано:
		<ul>
			<li>Показ всех процедур, которые связаны с врачом.</li>
			<li>Вызов  BX.PopupWindowManager по клике на процедуру.</li>
		</ul>
        </li>
	</ul>
	<h5 class="mb-3">3. Также дополнительно были реализовано следующие:</h5>
    	<ul>
			<li>Обработчик <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fajax%2Fbooking.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">AJAX</a>, в котором реализована логика записи и проверки занятости специалиста  (точное время. без учета выполнения процедуры).</li>
			<li>Класс <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fajax%2Fbooking.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">BookingPropertyValuesTable</a> для списка бронирования, наследованный от AbstractIblockPropertyValuesTable.</li>
		</ul>

</div>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>