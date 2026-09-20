<table width="100%" border="0">
    <tr>
        <td height="370" align="center" valign="top">
            <h2>CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM</h2>
            <h3>STUDENTS INDUSTRIAL WORK EXPERIENCE SCHEME (SIWES) UNIT</h3>
            <h3>LOG BOOK SCORE SHEET</h3>
            <br />
            <table width="100%" border="0" cellpadding="1" cellspacing="0" style="border-collapse:collapse">
                <tr align="left" style="border:solid 1px;">
                    <td height="22" style="border:solid 1px;">S/NO.</td>
                    <td width="24%" style="border:solid 1px;">STUDENT NAME</td>
                    <td width="15%" style="border:solid 1px;">REG NO</td>
                    <td width="12%" style="border:solid 1px;">SCORE</td>
                    <td width="28%" style="border:solid 1px;">FEEDBACK</td>
                    <td width="21%" style="border:solid 1px;">SUPERVISOR'S NAME</td>
                </tr>
                @forelse ($assessments as $assessment)
                    <tr align="left" style="border:solid 1px;">
                        <td width="5%" height="30" style="border:solid 1px;">&nbsp;<font size="2" face="Arial, Helvetica, sans-serif">{{ $loop->iteration }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $assessment->student?->user?->name ?? 'N/A' }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $assessment->student?->matric_no ?? 'N/A' }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $assessment->total_score }} / {{ $assessment->max_score }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $assessment->feedback }}</font></td>
                        <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $assessment->supervisor?->user?->name ?? 'N/A' }}</font></td>
                    </tr>
                @empty
                    <tr align="center" style="border:solid 1px;">
                        <td colspan="6" height="30" style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">No assessment records found.</font></td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
</table>
