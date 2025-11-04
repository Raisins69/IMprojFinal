<?php
// not_found.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0A0A0F;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            color: #fff;
        }

        .container {
            text-align: center;
        }

        h1 {
            font-size: 80px;
            font-weight: bold;
            margin: 0;
            color: #9b4de0;
        }

        p {
            font-size: 20px;
            margin: 10px 0 30px;
            color: #d3c3ff;
        }

        a {
            display: inline-block;
            text-decoration: none;
            background: #9b4de0;
            color: #fff;
            padding: 12px 22px;
            border-radius: 8px;
            transition: 0.3s ease;
        }

        a:hover {
            background: #7c3cc1;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>404</h1>
        <p>The page you’re looking for doesn’t exist.</p>
        <a href="index.php">Return to Home</a>
    </div>

</body>
</html>
