<?php

include "../util.php";

$access = new access( );

if (!$access->is_authenticated( )) {
    redirect("../auth/login.php");
    die( );
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Upload a file</title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="../style-elements.css">
    </head>
    <body>
        <main class="wrapper-login">
            <a href="index.php">Back to home</a>
            <p id="loading_notif" style="display: none"></p>
        <?php
                if (isset($_POST["btn_submit"])) { ?>
                    <p><?=$access->parse_upload($_POST["file_name"])?></p>
        <?php   }
            ?>
            <form class="container-upload" method="post" enctype="multipart/form-data">
                <input type="text" placeholder="Name (optional)" name="file_name" maxlength="20">
                <input type="file" name="uploaded_file">
                <button name="btn_submit" onclick="document.querySelector('#loading_notif').style.display = 'block';">Submit</button>
            </form>
        </main>
    </body>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</html>