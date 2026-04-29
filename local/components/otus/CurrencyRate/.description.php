<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    "NAME" => "Курс валюты",
    "DESCRIPTION" => "Выводит выбранную валюту и её курс.",
    "ICON" => "/images/news_curs.gif",
    "PATH" => array(
            "ID" => "otus",
            "CHILD" => array(
                "ID" => "table",
                "NAME" => "Курс валюты",
                "SORT" => 10,
                "CHILD" => array(
                    "ID" => "views",
                ),
            ),
        ),
];