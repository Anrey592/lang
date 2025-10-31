<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $USER;
if ($USER->IsAuthorized()) {
    if (isset($_REQUEST["backurl"]) && strlen($_REQUEST["backurl"]) > 0) {
        LocalRedirect($_REQUEST["backurl"]);
    }
    localRedirect('/personal/');
}

use Bitrix\Main\Page\Asset;

$APPLICATION->SetTitle("Авторизация");
?>
<?
$APPLICATION->IncludeComponent(
    "xillix:auth.popup",
    "auth.page",
    array()
);
?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>