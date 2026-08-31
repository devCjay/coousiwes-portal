<table width="100%" border="0">
    <tr>
        <td height="370" align="center" valign="top">
            <h2>CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM</h2>
            <h3>STUDENTS INDUSTRIAL WORK EXPERIENCE SCHEME (SIWES) UNIT</h3>
            <h3>{{ $title }}</h3>
            <br />
            <table width="100%" border="0" cellpadding="1" cellspacing="0" style="border-collapse:collapse">
                <tr align="left" class="fieldDarkText" style="border:solid 1px;">
                    <td height="22" style="border:solid 1px;">S/NO.</td>
                    <td width="24%" style="border:solid 1px;">STUDENT NAME</td>
                    <td width="15%" style="border:solid 1px;">MATRIC NUMBER</td>
                    <td width="20%" style="border:solid 1px;">DEPARTMENT</td>
                    <td width="20%" style="border:solid 1px;">FACULTY</td>
                    <td width="12%" style="border:solid 1px;">AMOUNT PAID</td>
                    <td width="12%" style="border:solid 1px;">PAYMENT METHOD</td>
                    <td width="16%" style="border:solid 1px;">PAYMENT DATE</td>
                </tr>
                @forelse ($payments as $payment)
                    <tr align="left" style="border:solid 1px;">
                        <td width="5%" height="30" style="border:solid 1px;">&nbsp;<font size="2" face="Arial, Helvetica, sans-serif">{{ $loop->iteration }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['name'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['matric_no'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['department'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['faculty'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['amount'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['method'] }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $payment['payment_date'] }}</font></td>
                    </tr>
                @empty
                    <tr align="center" style="border:solid 1px;">
                        <td colspan="8" height="30" style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">No payment records found.</font></td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>
