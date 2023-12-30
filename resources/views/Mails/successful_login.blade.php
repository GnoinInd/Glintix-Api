<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Successful Login Notification</title>
</head>
<body>
    <h1>Successful Login Notification</h1>
    <p>Hello {{ $user->name }},</p>
    <p>You have successfully logged in at {{ now() }}.</p>
</body>
</html>
