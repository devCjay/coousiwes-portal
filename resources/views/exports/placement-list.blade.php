@php
    $periodPart = function (?string $period, string $part): string {
        $normalized = strtoupper((string) ($period ?: 'APRIL TO OCTOBER'));

        if (! str_contains($normalized, ' TO ')) {
            return $part === 'from' ? 'APRIL' : 'OCTOBER';
        }

        [$from, $to] = array_map('trim', explode(' TO ', $normalized, 2));

        return $part === 'from' ? $from : $to;
    };

    $bankCode = function ($student): string {
        $metadata = $student->metadata ?? [];

        return (string) ($metadata['bank_code'] ?? $metadata['sort_code'] ?? '');
    };

    $groups = $placements->groupBy(fn ($placement) => ($placement->student?->faculty?->name ?? 'Unassigned').'|'.($placement->student?->department?->name ?? 'Unassigned'));
@endphp
<table width="100%" border="0">
    <tr>
        <td height="370" align="center" valign="top">
            <h2>CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM</h2>
            <h3>STUDENTS INDUSTRIAL WORK EXPERIENCE SCHEME (SIWES) UNIT</h3>
            <h3>PLACEMENT LIST FOR {{ $session }} SIWES PROGRAMME (APRIL-NOVEMBER {{ $year }} )</h3>
            <br />
            <table width="100%" border="0" cellpadding="1" cellspacing="0" style="border-collapse:collapse">
                @foreach ($groups as $group => $records)
                    @php
                        [$faculty, $department] = explode('|', $group, 2);
                    @endphp
                    <tr align="left" style="border:solid 1px;">
                        <td colspan="15" style="font-weight:bold">
                            <font size="3" face="Arial, Helvetica, sans-serif">
                                Faculty: {{ $faculty }}<br> Department: {{ $department }}
                            </font>
                        </td>
                    </tr>
                    <tr align="left" class="fieldDarkText" style="border:solid 1px;">
                        <td height="22" style="border:solid 1px;">S/NO.</td>
                        <td width="25%" style="border:solid 1px;">NAME_OF_STUDENT</td>
                        <td style="border:solid 1px;">REG_NO </td>
                        <td width="18%" style="border:solid 1px;">COURSE_OF_STUDY</td>
                        <td width="10%" style="border:solid 1px;">LEVEL_OF_STUDY</td>
                        <td width="10%" style="border:solid 1px;">PERIOD_OF_ATTACHEMNT_FROM</td>
                        <td width="10%" style="border:solid 1px;">PERIOD_OF_ATTACHEMNT_TO</td>
                        <td width="18%" style="border:solid 1px;">PLACEMENT_OF_ADDRESS</td>
                        <td width="18%" style="border:solid 1px;">BANK_CODE</td>
                        <td width="18%" style="border:solid 1px;">BANK_NAME</td>
                        <td width="18%" style="border:solid 1px;">ACCOUNT_NUMBER</td>
                        <td width="18%" style="border:solid 1px;">SORT_CODE</td>
                        <td width="18%" style="border:solid 1px;">SIWES_YEAR</td>
                        <td width="18%" style="border:solid 1px;">STUDENT_EMAIL_ADDRESS</td>
                        <td width="18%" style="border:solid 1px;">REMARKS</td>
                    </tr>
                    @foreach ($records as $placement)
                        @php
                            $student = $placement->student;
                            $metadata = $student?->metadata ?? [];
                            $level = $placement->academicLevel?->level ?? $student?->academicLevel?->level;
                            $address = trim(($placement->company_name ? $placement->company_name.' - ' : '').($placement->company_address ?? ''));
                        @endphp
                        <tr align="left" style="border:solid 1px;">
                            <td width="5%" height="30" style="border:solid 1px;">&nbsp;<font size="2" face="Arial, Helvetica, sans-serif">{{ $loop->iteration }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student?->user?->name }}</font></td>
                            <td width="10%" style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student?->matric_no }} </font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">&nbsp;{{ $student?->department?->name }} </font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">&nbsp;{{ $level }} </font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $periodPart($placement->attachment_period, 'from') }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $periodPart($placement->attachment_period, 'to') }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $address }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $bankCode($student) }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $metadata['bank_name'] ?? '' }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $metadata['account_number'] ?? '' }} </font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $metadata['sort_code'] ?? '' }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $placement->siwes_year }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student?->user?->email }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">  </font></td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        </td>
    </tr>
</table>
