@php
    $periodLabel = function ($placement) use ($year): string {
        $period = strtoupper((string) ($placement?->attachment_period ?? 'APRIL TO OCTOBER'));
        [$from, $to] = str_contains($period, ' TO ')
            ? array_map('trim', explode(' TO ', $period, 2))
            : ['APRIL', 'OCTOBER'];

        return "{$from} - {$to} {$year}";
    };

    $levelLabel = function ($student): string {
        $level = $student->placement?->academicLevel?->level ?? $student->academicLevel?->level;

        return $level ? "{$level} Level" : 'Level';
    };

    $groups = $students->groupBy(fn ($student) => ($student->faculty?->name ?? 'Unassigned').'|'.($student->department?->name ?? 'Unassigned'));
@endphp
<table width="100%" border="0">
    <tr>
        <td height="370" align="center" valign="top">
            <h2>CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM</h2>
            <h3>STUDENTS INDUSTRIAL WORK EXPERIENCE SCHEME (SIWES) UNIT</h3>
            <h3>MASTER LIST FOR {{ $session }} SIWES PROGRAMME (APRIL-NOVEMBER {{ $year }} )</h3>
            <br />
            <table width="100%" border="0" cellpadding="1" cellspacing="0" style="border-collapse:collapse">
                @foreach ($groups as $group => $records)
                    @php
                        [$faculty, $department] = explode('|', $group, 2);
                    @endphp
                    <tr align="left" style="border:solid 1px;">
                        <td colspan="7" style="font-weight:bold">
                            <font size="3" face="Arial, Helvetica, sans-serif">
                                Faculty: {{ $faculty }}<br> Department: {{ $department }}
                            </font>
                        </td>
                    </tr>
                    <tr align="left" class="fieldDarkText" style="border:solid 1px;">
                        <td height="22" style="border:solid 1px;">S/NO.</td>
                        <td width="25%" style="border:solid 1px;">NAME_OF_STUDENT</td>
                        <td style="border:solid 1px;">REG_NO</td>
                        <td width="18%" style="border:solid 1px;">COURSE_OF_STUDY</td>
                        <td width="18%" style="border:solid 1px;">LEVEL_OF_STUDY</td>
                        <td width="18%" style="border:solid 1px;">SIWES_YEAR</td>
                        <td width="18%" style="border:solid 1px;">STUDENT_EMAIL_ADDRESS</td>
                    </tr>
                    @foreach ($records as $student)
                        <tr align="left" style="border:solid 1px;">
                            <td width="5%" height="30" style="border:solid 1px;">&nbsp;<font size="2" face="Arial, Helvetica, sans-serif">{{ $loop->iteration }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student->user?->name }}</font></td>
                            <td width="10%" style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student->matric_no }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student->department?->name }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">&nbsp;{{ $levelLabel($student) }} </font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $periodLabel($student->placement) }}</font></td>
                            <td style="border:solid 1px;"><font size="2" face="Arial, Helvetica, sans-serif">{{ $student->user?->email }}</font></td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
        </td>
    </tr>
</table>
