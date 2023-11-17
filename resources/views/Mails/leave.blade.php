<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h2>Message From Hrms</h2><br>
    <h1>{{ $details['title'] }}</h1><br>
    <h3>Leave details </h3><br>
    <h1>Application date : &nbsp;&nbsp;{{ $details['date'] }} </h1>
    <h1>Applicant Name : &nbsp;&nbsp;{{ $details['applicantName'] }}</h1>
    <h1>Designation : &nbsp;&nbsp;{{ $details['designation'] }}</h1>
    <h1>Leave Type : &nbsp;&nbsp;{{ $details['leave_type'] }}</h1>
    <h1>From Date : &nbsp;&nbsp;{{ $details['startdate'] }}</h1>
    <h1>To Date : &nbsp;&nbsp;{{ $details['enddate'] }}</h1>
    <h1>No. of days : &nbsp;&nbsp;{{ $details['days'] }}</h1>
    <h1>Reason : &nbsp;&nbsp;{{ $details['reason'] }}</h1><br>
   
    <p>Thank you</p>
    
</body>
</html>