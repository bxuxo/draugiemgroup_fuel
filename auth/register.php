<?php

include "../util.php";

$access = new access( );

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register</title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="../style-elements.css">
    </head>
    <body>
        <main class="wrapper-login">
            <?php
                if (isset($_POST["btn_register"])) {
                    echo $access->register($_POST["in_username"], hash("sha256", $_POST["in_password"]));
                }
            ?>
            <form class="container-login" method="post">
                <input type="text" placeholder="Username..." name="in_username">
                <input type="text" placeholder="Password..." name="in_password">
                <button name="btn_register">Register</button>
                <a href="login.php">Already have an account? Login here</a>
            </form>
        </main>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>
    </body>
</html>