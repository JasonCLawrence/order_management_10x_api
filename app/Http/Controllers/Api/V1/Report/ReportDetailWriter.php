<?php

namespace App\Http\Controllers\Api\V1\Report;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class ReportDetailWriter
{
    public function writeOrder($sheet, $order, &$rowIndex, $options)
    {
        $this->writeHeader($sheet, $order, $rowIndex);
        if ($options->includeInvoice == true)
            $this->writeInvoice($sheet, $order, $rowIndex);

        if ($options->includeTasks == true)
            $this->writeTasks($sheet, $order, $rowIndex);

        if ($options->includeNotes == true)
            $this->writeNotes($sheet, $order, $rowIndex);

        if ($options->includeAuditLogs == true)
            $this->writeAuditLogs($sheet, $order, $rowIndex);
    }

    public function writeHeader($sheet, $order, &$rowIndex)
    {
        $sheet->mergeCells("A$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Order #" . $order->invoice_id);
        $sheet->getStyle("A$rowIndex")
            ->getAlignment()
            ->setHorizontal('center');
        $sheet->getStyle("A$rowIndex")->getFont()->setBold(true);
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "ID:");
        $sheet->setCellValue("B$rowIndex", $order->id);
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Order ID:");
        $sheet->setCellValue("B$rowIndex", $order->invoice_id);
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Customer");
        $sheet->setCellValue("B$rowIndex", $order->customer->name);
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Assignee:");
        $sheet->setCellValue("B$rowIndex", isset($order->driver) ? $order->driver->first_name . " " . $order->driver->last_name : "");
        $rowIndex++;

        // $sheet->setCellValue("A$rowIndex", "Warehouse:");
        // $sheet->setCellValue("B$rowIndex", $order->warehouse? $order->warehouse->name:"");
        // $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Description");
        $sheet->mergeCells("B$rowIndex:E$rowIndex");
        $sheet->setCellValue("B$rowIndex", $order->description);
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Released By");
        $sheet->setCellValue("B$rowIndex", $order->warehouse_signed ? $order->warehouse_signee : "N/A");
        $rowIndex++;

        $sheet->setCellValue("A$rowIndex", "Signed By");
        $sheet->setCellValue("B$rowIndex", $order->signature ? $order->signee : "N/A");
        $rowIndex++;

        $rowIndex++;
    }

    public function writeInvoice($sheet, $order, &$rowIndex)
    {
        $items = $order->invoiceItems;
        if (count($items) == 0)
            return;

        // TITLE
        $sheet->mergeCells("A$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Invoice");
        $sheet->getStyle("A$rowIndex")
            ->getAlignment()
            ->setHorizontal('center');
        $rowIndex++;

        // HEADER
        $sheet->mergeCells("A$rowIndex:B$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Item");
        $sheet->setCellValue("C$rowIndex", "Quantity");
        $sheet->setCellValue("D$rowIndex", "Price");
        $sheet->setCellValue("E$rowIndex", "Total");
        $rowIndex++;

        // ITEMS
        foreach ($items as $item) {
            $sheet->mergeCells("A$rowIndex:B$rowIndex");
            $sheet->setCellValue("A$rowIndex", $item->item);
            $sheet->setCellValue("C$rowIndex", $item->quantity);
            $sheet->setCellValue("D$rowIndex", $item->price);
            $sheet->setCellValue("E$rowIndex", $item->quantity * $item->price);


            $sheet->getStyle("D$rowIndex")
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $sheet->getStyle("E$rowIndex")
                ->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $rowIndex++;
        }


        $rowIndex++;
    }

    public function writeTasks($sheet, $order, &$rowIndex)
    {
        $items = $order->checklist;
        if (count($items) == 0)
            return;

        // TITLE
        $sheet->mergeCells("A$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Tasks");
        $sheet->getStyle("A$rowIndex")
            ->getAlignment()
            ->setHorizontal('center');
        $rowIndex++;

        // HEADER
        $sheet->mergeCells("B$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Checked");
        $sheet->setCellValue("B$rowIndex", "Item");
        $rowIndex++;

        // ITEMS
        foreach ($items as $item) {
            $sheet->mergeCells("B$rowIndex:E$rowIndex");
            $sheet->setCellValue("A$rowIndex", $item->checked == 1 ? "yes" : "no");
            $sheet->setCellValue("B$rowIndex", $item->name);
            $rowIndex++;
        }

        $rowIndex++;
    }

    public function writeNotes($sheet, $order, &$rowIndex)
    {
        $items = $order->notes;
        if (count($items) == 0)
            return;

        // TITLE
        $sheet->mergeCells("A$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Notes");
        $sheet->getStyle("A$rowIndex")
            ->getAlignment()
            ->setHorizontal('center');
        $rowIndex++;

        // HEADER
        // $sheet->mergeCells("B$rowIndex:E$rowIndex");
        // $sheet->setCellValue("A$rowIndex", "Checked");
        // $sheet->setCellValue("B$rowIndex", "Item");
        // $rowIndex++;

        // ITEMS
        foreach ($items as $item) {
            $sheet->mergeCells("A$rowIndex:E$rowIndex");
            $sheet->setCellValue("A$rowIndex", $item->content);
            $rowIndex++;
        }

        $rowIndex++;
    }

    public function writeAuditLogs($sheet, $order, &$rowIndex)
    {
        $items = $order->auditLogs;
        if (count($items) == 0)
            return;

        // TITLE
        $sheet->mergeCells("A$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Audit Logs");
        $sheet->getStyle("A$rowIndex")
            ->getAlignment()
            ->setHorizontal('center');
        $rowIndex++;

        // HEADER
        $sheet->mergeCells("C$rowIndex:E$rowIndex");
        $sheet->setCellValue("A$rowIndex", "Type");
        $sheet->setCellValue("B$rowIndex", "Date/Time");
        $sheet->setCellValue("C$rowIndex", "Message");
        $rowIndex++;

        // ITEMS
        foreach ($items as $item) {
            $sheet->mergeCells("C$rowIndex:E$rowIndex");
            $sheet->setCellValue("A$rowIndex", $item->type);
            $sheet->setCellValue("B$rowIndex", $item->created_at);
            $sheet->setCellValue("C$rowIndex", $this->formatAuditMessage($item->message));
            $rowIndex++;
        }


        $rowIndex++;
    }

    function formatAuditMessage($msg)
    {
        $msg = str_replace("<br/>", "\n", $msg);
        $msg = str_replace("&bull;", "- ", $msg);
        $msg = strip_tags($msg);

        return $msg;
    }
}
