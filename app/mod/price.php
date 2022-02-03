<?php
/**
 * @package price
 * @author <a.sirotkin>
 * @since  2022-02-02
 */

require_once(MOD_DIR . "main.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class price extends main
{
    protected $pgStorage;

    public function __construct()
    {
        global $dsnPgSql;
        parent::__construct();
        $this->pgStorage = new PgSqlStorage($dsnPgSql, "price");
    }

    public function show($paramRows = [])
    {
        $this->data["metaRows"] = [
            "title" => "price",
        ];
    }

    public function providerRows($paramRows = [])
    {
        $this->data["providerRows"] = $this->pgStorage->selectRows("provider", $this->getConditionRows($paramRows));
    }

    public function itemRows($paramRows = [])
    {
        $this->data["itemRows"] = $this->pgStorage->selectRows("item", $this->getConditionRows($paramRows));
    }

    public function priceListRows($paramRows = [])
    {
        if (empty($paramRows["newDate"])) {
            $paramRows["newDate"] = date("Y-m-d");
        }
        $this->data["date"] = $paramRows["newDate"];
        unset($paramRows["newDate"]);

        $this->data["requestStr"] = http_build_query($paramRows);
        $conditionRows = $this->getConditionRows($paramRows);
        $conditionRows[] = "\"date\" >= '{$this->data["date"]}'";
        $this->data["priceListRows"] = $this->pgStorage->selectRows("price_list", $conditionRows);
        foreach ($this->data["priceListRows"] as &$priceListRow) {
            $priceListRow["providerRow"] = $this->pgStorage->selectRow("provider", ["id" => $priceListRow["id_provider"]]);
            if (!$paramRows or empty($paramRows["id"])) {
                continue;
            }
            $priceListRow["priceItemRows"] = $this->pgStorage->selectRows("price_item", ["id_price_list" => $priceListRow["id"]]);
        }
    }

    public function priceItemLogRows($paramRows = [])
    {
        $query = "
SELECT pil.*, p.name AS name_provider, pl.name AS name_price_list, i.name AS name_item, i.article AS article_item
  FROM price_item_log AS pil
  JOIN price_item AS pi ON pil.id_price_item = pi.id
  JOIN price_list AS pl ON pi.id_price_list = pl.id
  JOIN item AS i ON pi.id_item = i.id
  JOIN provider AS p ON pl.id_provider = p.id
 ORDER BY pil.created DESC";
        $this->data["priceItemLogRows"] = $this->pgStorage->queryRows($query);
    }

    public function priceListRow($paramRows = [])
    {
        $this->data["itemRows"] = [
            0 => "Выберите"
        ];
        $itemRows = $this->pgStorage->selectRows("item", ["active" => "true"]);
        foreach ($itemRows as $itemRows) {
            $this->data["itemRows"][$itemRows["id"]] = "{$itemRows["name"]} (арт. {$itemRows["article"]})";
        }
        $this->data["priceListRow"] = $this->pgStorage->selectRow("price_list", $this->getConditionRows($paramRows));
        $this->data["priceListRow"]["providerRow"]      = $this->pgStorage->selectRow("provider", ["id" => $this->data["priceListRow"]["id_provider"]]);
        $this->data["priceListRow"]["priceItemRows"]    = $this->pgStorage->selectRows("price_item", ["id_price_list" => $this->data["priceListRow"]["id"]], "_updated DESC");
        foreach ($this->data["priceListRow"]["priceItemRows"] as &$priceItemRow) {
            $priceItemRow["itemRow"] = $this->pgStorage->selectRow("item", ["id" => $priceItemRow["id_item"]]);
        }
    }

    public function savePriceItem($priceItemRow = [])
    {
        $result = ["success" => true];
        try {
            $this->pgStorage->begin();
            if ("new" == $priceItemRow["id"]) {
                unset($priceItemRow["id"]);
                $priceItemRow["id"] = $this->pgStorage->insertRow("price_item", $priceItemRow);
                $priceItemLogRow = [
                    "id_price_item" => $priceItemRow["id"],
                    "new_price"     => $priceItemRow["id"],
                    "new_active"    => "true",
                ];
            } else {
                $priceItemOldRow = $this->pgStorage->selectRow("price_item", ["id" => $priceItemRow["id"]]);
                $priceItemLogRow = [
                    "id_price_item" => $priceItemOldRow["id"],
                    "old_price"     => $priceItemOldRow["price"],
                    "old_active"    => $priceItemOldRow["active"],
                    "new_price"     => isset($priceItemRow["price"]) ? $priceItemRow["price"] : $priceItemOldRow["price"],
                    "new_active"    => isset($priceItemRow["active"]) ? $priceItemRow["active"] : $priceItemOldRow["active"],
                ];
                $priceItemRow["id"] = $this->pgStorage->updateRow("price_item", $priceItemRow);
            }
            $this->pgStorage->insertRow("price_item_log", $priceItemLogRow);
            $this->pgStorage->commit();
        } catch (Exception $e) {
            $result["success"] = false;
        }
        header("Content-type: application/json");
        echo (json_encode($result));
        exit;
    }

    public function priceListRowExport($paramRows = [])
    {
        if (empty($paramRows[0])) {
            $paramRows = $_GET;
        }
        $this->priceListRow($paramRows);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();
#        $activeSheet->setTitle($this->data["priceItemRow"]["name"]);

        $activeSheet->setCellValue("A1", "№");
        $activeSheet->setCellValue("B1", "Артикул");
        $activeSheet->setCellValue("C1", "Название");
        $activeSheet->setCellValue("D1", "Стоимость");
        $activeSheet->setCellValue("E1", "Валюта");

        foreach ($this->data["priceListRow"]["priceItemRows"] as $priceItemKey => $priceItemRow) {
            $numRow = $priceItemKey + 2;
            $activeSheet->setCellValue("A" . $numRow, $priceItemKey + 1);
            $activeSheet->setCellValueExplicit("B" . $numRow, $priceItemRow["itemRow"]["article"], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit("C" . $numRow, $priceItemRow["itemRow"]["name"], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $activeSheet->setCellValueExplicit("D" . $numRow, $priceItemRow["price"], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $activeSheet->setCellValueExplicit("E" . $numRow, $this->data["priceListRow"]["currency"], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        header("Content-Type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=\"{$this->data["priceListRow"]["name"]}.xls\"");
        $objXlsx = new Xls($spreadsheet);
        $objXlsx->save("php://output");
        exit;
    }
}