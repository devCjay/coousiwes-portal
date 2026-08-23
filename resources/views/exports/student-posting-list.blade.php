<table border='1'><tr><td colspan='10'><b>CHUKWUEMEKA ODIMEGWU OJUKWU UNIVERSITY, IGBARIAM</b></td></tr><tr><td colspan='10'></td></tr><tr><td colspan='10'><b>STUDENTS INDUSTRIAL WORK EXPERIENCE SCHEME (SIWES) UNIT</b></td></tr><tr><td colspan='10'></td></tr><tr><td colspan='10'><b>SUPERVISORY LIST BY STATE FOR {{ $session }} SIWES PROGRAMME (APRIL-NOVEMBER {{ $year }} )</b></td></tr><tr><td colspan='10'></td></tr><tr>
        <td><b>SN.</b></td>
        <td><b>Name of Student</b></td>
        <td><b>Matric NO.</b></td>
        <td><b>Course of Study</b></td>
        <td><b>State</b></td>
        <td><b>Location</b></td>
        <td><b>Company</b></td>
        <td><b>Address</b></td>
        <td><b>Company Supervisor Contact</b></td>
        <td><b>Student Contact</b></td>
      </tr>@foreach ($placements as $placement)<tr>
        <td>{{ $loop->iteration }}</td>
        <td>{{ $placement->student->user->name }}</td>
        <td>{{ $placement->student->matric_no }}</td>
        <td>{{ $placement->student->department?->name }}</td>
        <td>{{ $placement->company_state }}</td>
        <td>{{ $placement->company_lga }}</td>
        <td>{{ $placement->company_name }}</td>
        <td>{{ $placement->company_address }}</td>
        <td>{{ $placement->company_supervisor_phone }}</td>
        <td>{{ $placement->student->user->phone }}</td>
      </tr>@endforeach</table>
