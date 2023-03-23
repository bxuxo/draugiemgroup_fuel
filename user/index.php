<?php

include "../util.php";

$access = new access( );

if (!$access->is_authenticated( )) {
    redirect("../auth/login.php");
    die( );
}

if (isset($_POST["logout_btn"])) {
    $access->log_out( );
}

if (isset($_POST["newupload_btn"])) {
    redirect("upload.php");
}

$user_uploads = $access->get_current_user_uploads( );

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Homepage</title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="../style-elements.css">
    </head>
    <body>
        <main class="wrapper-home">
            <header>
                <p>Welcome back, <?=$_SESSION["uname"]?></p>
                <form method="post">
                    <button name="logout_btn">Log out</button>
                </form>
            </header>
            <form class="my-uploads" method="post">
            <?php
                 foreach ($user_uploads as $upload) { 
                    if (isset($_POST["upload_" . $upload["id"]])) {
                        redirect("view.php?what=" . $_SESSION["uid"] . "-" . $upload["id"]);
                    }
            ?>
                    <button class="btn-view-upload" name="upload_<?=$upload["id"]?>"><?=$upload["upload_name"]?></button>
            <?php }
            ?>
                <button class="btn-new-upload" name="newupload_btn">+</button>
            </form>
        </main>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState( null, null, window.location.href );
            }
        </script>
    </body>
</html>