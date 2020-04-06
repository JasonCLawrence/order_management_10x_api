<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>OMS</title>
    <style type="text/css" rel="stylesheet" media="all">
        /* Base ------------------------------ */
        *:not(br):not(tr):not(html) {
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        body {
            width: 100% !important;
            height: 100%;
            margin: 0;
            line-height: 1.4;
            background-color: #F2F4F6;
            color: #74787E;
            -webkit-text-size-adjust: none;
        }

        a {
            color: #3869D4;
        }

        /* Layout ------------------------------ */
        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #F2F4F6;
        }

        .email-content {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* Masthead ----------------------- */
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }

        .email-masthead_logo {
            max-width: 400px;
            border: 0;
        }

        .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #bbbfc3;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }

        /* Body ------------------------------ */
        .email-body {
            width: 100%;
            margin: 0 4em;
            padding: 0;
            border-top: 1px solid #EDEFF2;
            border-bottom: 1px solid #EDEFF2;
            background-color: #FFF;
        }

        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 0;
        }

        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 0;
            text-align: center;
        }

        .email-footer p {
            color: #AEAEAE;
        }

        .body-action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            text-align: center;
        }

        .body-sub {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #EDEFF2;
        }

        .content-cell {
            padding: 35px;
        }

        .align-right {
            text-align: right;
        }

        /* Table ----------------------------- */
        .items-table {
            width: 100%;
            border: solid #d4d4d4 1px;
            margin-bottom: 1em;
            padding: 0;
            border-collapse: collapse;
        }

        .items-table th {
            text-align: left;
            padding: 0.5em;
        }

        .items-table td {
            text-align: left;
            padding: 0.5em;
        }

        .items-table .table-header {
            background: #404040;
            color: white;
            line-height: 1.4;
            text-align: center;
        }

        .items-table .table-header th {
            padding: 0.5em;
            text-align: center;
            font-weight: normal;
        }

        .items-table .table-sub-header {
            background: #ababab;
            color: white;
        }

        .items-table .table-sub-header th {
            font-weight: normal;
        }

        .center-text {
            text-align: center;
        }

        /* Type ------------------------------ */
        h1 {
            margin-top: 0;
            color: #2F3133;
            font-size: 19px;
            font-weight: bold;
            text-align: left;
        }

        h2 {
            margin-top: 0;
            color: #2F3133;
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }

        h3 {
            margin-top: 0;
            color: #2F3133;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }

        p {
            margin-top: 0;
            color: #74787E;
            font-size: 16px;
            line-height: 1.5em;
            text-align: left;
        }

        p.sub {
            font-size: 12px;
        }

        p.center {
            text-align: center;
        }

        /* Buttons ------------------------------ */
        .button {
            display: inline-block;
            width: 200px;
            background-color: #3869D4;
            border-radius: 3px;
            color: #ffffff;
            font-size: 15px;
            line-height: 45px;
            text-align: center;
            text-decoration: none;
            -webkit-text-size-adjust: none;
            mso-hide: all;
        }

        .button--green {
            background-color: #22BC66;
        }

        .button--red {
            background-color: #dc4d2f;
        }

        .button--blue {
            background-color: #3869D4;
        }

        /*Media Queries ------------------------------ */
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
                    <!-- Logo -->
                    <tr>
                        <td class="email-masthead">
                            <a class="email-masthead_name"><img src="https://10xfleet.com" alt="10xfleet" width="200" title="10xfleet" /></a>
                        </td>
                    </tr>
                    <!-- Email Body -->
                    <tr>
                        <td class="email-body" width="100%">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell">
                                        <h1>Hello {{$customerName}},</h1>

                                        <p>

                                            Your order has been complete on {{$orderDate}} at {{$orderTime}}.<br />

                                        </p>
                                        @if(count($invoiceItems) > 0)
                                        <table class="items-table">
                                            <tr class="table-header">
                                                <th colspan="4">Invoice</th>
                                            </tr>
                                            <tr class="table-sub-header">
                                                <th width="60%">Description</th>
                                                <th>Quantity</th>
                                                <th>Price</th>
                                                <th>Total</th>
                                            </tr>
                                            @foreach ($invoiceItems as $items)
                                            <tr>
                                                <td>{{$items->item}}</td>
                                                <td class="center-text">{{$items->quantity}}</td>
                                                <td>${{number_format($items->price, 2)}}</td>
                                                <td>${{number_format($items->price * $items->quantity, 2)}}</td>
                                            </tr>
                                            @endforeach
                                            <tr>
                                                <td></td>
                                                <td></td>
                                                <td><b>Total:</b></td>
                                                <td>${{number_format($invoiceTotal, 2)}}</td>
                                            </tr>
                                        </table>
                                        @endif
                                        @if(count($tasks) > 0)
                                        <table class="items-table">
                                            <tr class="table-header">
                                                <th colspan="2" align="center">Tasks</th>
                                            </tr>
                                            <tr class="table-sub-header">
                                                <th width="90%">Name</th>
                                                <th>Complete</th>
                                            </tr>
                                            @foreach ($tasks as $task)
                                            <tr>
                                                <td>{{$task->name}}</td>
                                                <td>{{$task->checked?'yes':'no'}}</td>
                                            </tr>
                                            @endforeach
                                        </table>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="content-cell">
                                        <p class="sub center">&copy; {{date("Y")}} OMS. All rights reserved.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>