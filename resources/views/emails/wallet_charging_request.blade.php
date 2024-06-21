<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Wallet Charging Request</title>
    <style>
        .container {
            background-color: rgb(216, 216, 216);
            margin: 50px 50px;
            border-radius: 10px;
            padding: 20px;
        }

        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h4 {
            display: inline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div>
            <h2 style="text-align: center;">Wallet Charging Request from {{$username}}</h2>
        </div>
        <h4>UserName:</h4>
        <span>{{$username}}</span>
        </br>
        <h4>Email:</h4>
        <span>{{$email}}</span>
        </br>

        <h4>Phone:</h4>
        <span>{{$phone}}</span>
    </div>
</body>

</html>
