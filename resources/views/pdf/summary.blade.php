<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice</title>

    <style>
        @page {
            padding: 0px;
            margin: 0px;
            width: 595;
            height: 842;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .invoice-box table {
            width: 100%;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
            line-height: 30px;
        }

        .invoice-box table tr td:nth-child(2) {}

        .items-table tr td {
            padding: 5pt;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading {
            border-radius: 5px;
            background: #222;
        }

        .invoice-box table tr.heading td {
            font-weight: bold;
            color: white;
            padding: 5px 20px;
            line-height: 20px;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: none;
            padding: 8px 20px;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        /* SUBTOTAL */
        .invoice-box table tr.subtotal td.total-title {
            border-top: none;
            padding: 5px 20px;
            line-height: 20px;
            text-align: right;
        }

        .invoice-box table tr.subtotal td.total-item {
            border-top: none;
            padding: 5px 20px;
            line-height: 20px;
        }

        /* TAX */
        .invoice-box table tr.tax td.total-title {
            border-top: none;
            padding: 5px 20px;
            line-height: 20px;
            text-align: right;
        }

        .invoice-box table tr.tax td.total-item {
            border-top: none;
            padding: 5px 20px;
            line-height: 20px;
        }

        /* TOTAL */
        .invoice-box table tr.total td.total-title {
            border-top: none;
            font-weight: bold;
            padding: 5px 20px;
            line-height: 20px;
            background: #ddd;
            text-align: right;
        }

        .invoice-box table tr.total td.total-item {
            border-top: none;
            font-weight: bold;
            padding: 5px 20px;
            line-height: 20px;
            background: #ddd;
        }

        /** RTL **/
        .rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .rtl table {
            text-align: right;
        }

        .rtl table tr td:nth-child(2) {}

        .notes {
            margin-top: 40px;
            color: #555
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td>
                    <div style="width:300pt;"> </div>
                </td>
                <td></td>
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                            </td>

                            <td>
                                <h1>{{ $order->customer->name }}</h1>
                                <b>#</b> {{ $order->invoice_number }}<br>
                                <b>Order Created:</b> {{ \Carbon\Carbon::parse($order->schedule_at)->toFormattedDateString()}}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td><span style="color:#555;">Bill To:</span><br /><b>{{ $order->customer->name }}</b><br />{!! nl2br(e($order->customer->address)) !!}</td>
                <td></td>
                <td colspan="2">
                    <table>
                        <tr>
                            <td>

                            </td>

                            <td>
                                {{ $company->name }}<br>
                                {{ $company->street }}<br>
                                {{ $company->city }}<br>
                                {{ $company->country }}<br>
                                {{ $company->email }}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="items-table" cellpadding="0" cellspacing="0">
            <tr class="heading">
                <td style="border-radius:4px 0 0 4px" width="50%">
                    Item
                </td>

                <td>
                    Quantity
                </td>

                <td>
                    Price
                </td>

                <td style="border-radius:0 4px 4px 0">
                    Total
                </td>
            </tr>

            @foreach ($order->invoiceItems as $item)
            <tr class="item">
                <td width="50%">
                    {{ $item->item }}
                </td>

                <td>
                    {{ number_format($item->quantity) }}
                </td>

                <td>
                    ${{ number_format($item->price, 2) }}
                </td>

                <td>
                    ${{ number_format($item->price * $item->quantity, 2) }}
                </td>
            </tr>
            @endforeach

            <!-- <tr class="tax">
                <td width="50%"></td>
                <td></td>
                <td class="total-title"><b>Sales Tax:</b></td>
                
                <td class="total-item">
                   <b>${{ number_format($order->sales_tax, 2) }}</b>
                </td>
            </tr> -->
            <tr class="total">
                <td width="50%"></td>
                <td></td>
                <td class="total-title"><b>Total:</b></td>

                <td class="total-item">
                    <b>${{ number_format($order->total, 2) }}</b>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>