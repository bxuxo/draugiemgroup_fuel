<?php

include "../util.php";

$access = new access( );

if (!$access->is_authenticated( )) {
    redirect("../auth/login.php");
    die( );
}

if (isset($_POST["btn_back"])) {
    redirect("index.php");
    die( );
}

$given_data = explode("-", @$_GET["what"]);
$user_id = @$given_data[0];
$upload_id = @$given_data[1];

$upload_info = $access->get_upload_info($user_id, $upload_id);
if ($upload_info == null) {
    die("An error has occured. Please return to the homepage and try the same action again. If it still doesn't work, please re-login. Contact an administrator if this issue persists.");
}

// arguments of how the user has requested to filter get passed, gets accordingly filtered then displayed
$args = []; $status = ""; // create args file if config was pressed to save it 
if (isset($_POST["btn_filter"]) || isset($_POST["config_create"])) {
    $begin_date = $_POST["filter_date_begin"];
    $end_date = $_POST["filter_date_end"];
    if (($begin_date == null && $end_date != null) || ($begin_date != null && $end_date == null)) {
        $status = "To filter by date, both dates must be selected";
    } else if (strtotime($begin_date) > strtotime($end_date)) {   
        $status = "Beggining date can't be later than end date";
    } else if ($begin_date != null && $end_date != null) {
        $args["filter_date"] = [$begin_date, $end_date];
    }

    if ($_POST["filter_fuelcard"] != "") {
        $args["filter_fuelcard"] = $_POST["filter_fuelcard"];
    }
    if ($_POST["filter_vehiclenr"] != "") {
        $args["filter_vehiclenr"] = $_POST["filter_vehiclenr"];
    }
    if ($_POST["filter_fueltype"] != "Don't filter by fuel type") {
        $args["filter_fueltype"] = $_POST["filter_fueltype"];
    }
    if ($_POST["sort_by"] != "Don't sort") {
        $args["sort_by"] = $_POST["sort_by"];
    }
}

if (isset($_POST["config_create"])) {
    if ($_POST["config_name"] == "") {
        $status = "To create a config, you must input it's name.";
    } else {
        $access->add_configuration($upload_id, $_POST["config_name"], $args);
    }
}

$saved_configs = $access->get_upload_configs($upload_id);
if (sizeof($saved_configs) >= 1) {
    foreach ($saved_configs as $config) {
        if (isset($_POST["btn_config_" . $config["id"]])) {
            $args = json_decode($config["filters_json"], true); // sets the selected config
        }
    }
}

$json_data = json_decode(file_get_contents($upload_info["upload_path"]), true);
$processed = csv::filter($json_data, $args);
$summary = $processed["summary"];

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Viewing file <?=$upload_info["upload_name"]?></title>
        <link rel="stylesheet" href="../style.css">
        <link rel="stylesheet" href="../style-elements.css">
    </head>
    <body>
        <main class="wrapper-view">
            <header>
                <p>Viewing file <?=$upload_info["upload_name"]?></p>
                <p><?=$status?></p>
                <form method="post">
                    <button name="btn_back">Back to home</button>
                </form>
            </header>
            <section class="content-section">
                <form class="filter-aside" method="post">
                    <u>Filter by: </u>
                    <div class="filter-div">
                        <p>Period</p>
                        <div class="filter-date-div">
                            <span class="date-overlay-wrapper">
                                <input type="date" name="filter_date_begin" value="<?=@$args["filter_date"][0]?>">
                                <p>Date from</p>
                            </span>
                            <span class="date-overlay-wrapper">
                                <input type="date" name="filter_date_end" value="<?=@$args["filter_date"][1]?>">
                                <p>Date to</p>
                            </span>
                        </div>
                    </div>
                    <div class="filter-div">
                        <p>Fuel type</p>
                        <select name="filter_fueltype">
                            <option val="Don't filter by fuel type" selected>Don't filter by fuel type</option>
                            <option val="Diesel">Diesel</option>
                            <option val="Gasoline, 98">Gasoline, 98</option>
                            <option val="Gasoline, 95">Gasoline, 95</option>
                            <option val="Electricity">Electricity</option>
                            <option val="CNG (compressed natural gas)">CNG (compressed natural gas)</option>
                        </select>
                    </div>
                    <div class="filter-div">
                        <p>Fuel card nr.</p>
                        <input type="text" placeholder="Leave empty to not filter" name="filter_fuelcard" value="<?=@$args["filter_fuelcard"]?>">
                    </div>
                    <div class="filter-div">
                        <p>Vehicle nr.</p>
                        <input type="text" placeholder="Leave empty to not filter" name="filter_vehiclenr" value="<?=@$args["filter_vehiclenr"]?>">
                    </div>
                    <div class="filter-div">
                        <u>Sort by: </u>
                        <select name="sort_by">
                            <option val="Don't sort">Don't sort</option>
                            <option val="Sort by date, ascending">Sort by date, ascending</option>
                            <option val="Sort by date, descending">Sort by date, descending</option>
                            <option val="Sort by fuel type, ascending">Sort by fuel type, ascending</option>
                            <option val="Sort by fuel type, descending">Sort by fuel type, descending</option>
                        </select>
                    </div>
                    <button name="btn_filter">Filter & sort</button>
                    <div class="filter-div">
                        <u>Summary about this search:</u>
                        <p><?=$summary["amount"]?> transactions</p>
                        <p>Total: <?=$summary["total_all"]?> EUR</p>
                        <p>Diesel: <?=$summary["total_diesel"]?> EUR</p>
                        <p>CNG: <?=$summary["total_cng"]?> EUR</p>
                        <p>Gasoline, 95: <?=$summary["total_gasoline95"]?> EUR</p>
                        <p>Gasoline, 98: <?=$summary["total_gasoline98"]?> EUR</p>
                        <p>Electricity: <?=$summary["total_electricity"]?> EUR</p>
                    </div>
                    <div class="filter-div">
                        <u>Saved configurations for this upload:</u>
                        <div class="saved-configs">
                        <?php   if (sizeof($saved_configs) >= 1) {
                                foreach ($saved_configs as $config) { ?>
                                    <button name="btn_config_<?=$config["id"]?>"><?=$config["config_name"]?></button>
                    <?php       }?>
                    <?php   } else {?>
                                <p>No configs for this upload.</p>
                    <?php   }?>
                        </div>
                        <u>Create a new configuration:</u>
                        <div class="create-config">
                            <input type="text" placeholder="Config name" name="config_name">
                            <button name="config_create">Save current filters and create config</button>
                        </div>
                    </div>
                </form>
                <div class="table-wrapper">
                <?php if (sizeof($processed["data"]) != 0) { ?>
                    <table>
                        <tr>
                            <th>Date & time</th>
                            <th>Fuel card nr.</th>
                            <th>Vehicle nr.</th>
                            <th>Fuel type</th>
                            <th>Amount</th>
                            <th>Total sum (EUR)</th>
                            <th>~ Cost per 1 unit</th>
                            <th>Fuel station name</th>
                            <th>CAN distance (km)</th>
                            <th>GPS distance (km)</th>
                            <th>GPS coordinates</th>
                        </tr>
                <?php   foreach ($processed["data"] as $upload_row) { ?>
                            <tr>
                                <td><?=$upload_row["datetime"]?></td>
                                <td><?=$upload_row["fuel_card"]?></td>
                                <td><?=$upload_row["vehicle"]?></td>
                                <td><?=$upload_row["fuel_type"]?></td>
                                <td><?=$upload_row["amount"]?></td>
                                <td><?=$upload_row["total_sum"]?></td>
                                <td><?=$upload_row["per_unit"]?></td>
                                <td><?=$upload_row["fuel_station"]?></td>
                                <td><?=isset($upload_row["can_distance"]) ? $upload_row["can_distance"] . " km" : "No info."?></td>
                                <td><?=isset($upload_row["gps_distance"]) ? $upload_row["gps_distance"] . " km" : "No info."?></td>
                                <td><?=isset($upload_row["gps_distance"]) ? "Lat: " . $upload_row["position_lat"] . " Long: " . $upload_row["position_lng"] : "No info."?></td>
                            </tr>
                <?php   } ?>
                    </table>
                <?php } else { ?>
                        <p>No transactions found that abide your filters.</p>
                <?php } ?>
                </div>
            </section>
        </main>
    </body>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        let fueltype_select_value = `<?php echo @$args["filter_fueltype"]; ?>`;
        if (fueltype_select_value == "")
            fueltype_select_value = "Don't filter by fuel type";

        document.querySelector("[name=filter_fueltype]").value = fueltype_select_value;

        let sortby_select_value = `<?php echo @$args["sort_by"]; ?>`;
        if (sortby_select_value == "")
            sortby_select_value = "Don't sort";

        document.querySelector("[name=sort_by]").value = sortby_select_value;
    </script>
</html>