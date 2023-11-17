<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    @if($errors->any())
    <ul>
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    @endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf 
        
        
        <input type="hidden" name="email" value="{{ $usersData }}">
        
        <input type="password" name="password" placeholder="New Password">
        <br><br>
        <input type="password" name="password_confirmation" placeholder="Confirn Password">
        
        <br><br>
        <input type="submit">
    </form>
</body>
</html>