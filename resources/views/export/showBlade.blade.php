<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data</title>
</head>
<body>
    <h1>Export Data</h1>
    <form action="{{ url('export-project-data-test') }}" method="post">
        @csrf 
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from_date" required>
        <br>
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to_date" required>
        <br>
        <button type="submit">Export</button>
    </form>
</body>
</html>
