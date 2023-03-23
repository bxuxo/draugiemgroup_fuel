<?php

function redirect($where) {
    header("Location: $where");
}

function pretty_array($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

class access {
    private $cnc;
    function __construct( ) {
        $this->cnc = new mysqli("localhost", "root", "", "fuel");
    }

    // main
    function login($in_username, $in_password): bool {
        $sql = "SELECT * FROM users WHERE uname=? AND pword=?";
        $result = $this->cnc->execute_query($sql, [$in_username, $in_password])->fetch_all(MYSQLI_ASSOC);
        if (sizeof($result) > 0) {
            session_start( );
            $_SESSION["uname"] = $in_username;
            $_SESSION["uid"] = $result[0]["id"];

            // print_r($result);

            return true;
        }

        return false;
    }

    function register($in_username, $in_password): string {
        if (strlen($in_username) < 3) {
            return "Your username should be atleast 3 characters.";
        }

        if (strlen($in_password) < 6) {
            return "Your password should be atleast 6 characters.";
        }

        $sql = "INSERT INTO users (uname, pword) VALUES (?, ?)";
        $this->cnc->execute_query($sql, [$in_username, $in_password]);

        return "Successfully registered, you may login.";
    }

    function is_authenticated( ): bool {
        session_start( );
        return isset($_SESSION["uname"]);
    }
    
    function log_out( ): void {
        session_destroy( );
        redirect("../auth/login.php");
    }

    function get_current_user_uploads( ): array {
        $row = $this->cnc->execute_query("SELECT * FROM uploads WHERE owner_id=?", [$_SESSION["uid"]])->fetch_all(MYSQLI_ASSOC);
        if ($row == null)
            return [];

        return $row;
    }

    function get_upload_info($uid, $upload_id): array | null {
        $user_row = $this->cnc->execute_query("SELECT * FROM users WHERE id=?", [$uid])->fetch_all(MYSQLI_ASSOC);
        if (sizeof($user_row) < 1) {
            return null;
        }

        $user_row_c = $user_row[0];
        if ($user_row_c["uname"] != $_SESSION["uname"]) {
            return null;
        }

        $uploads_arr = $this->cnc->execute_query("SELECT * FROM uploads WHERE owner_id=? AND id=?", [$uid, $upload_id])->fetch_all(MYSQLI_ASSOC);
        return @$uploads_arr[0];
    }

    function add_configuration($upload_id, $config_name, $filters): void {
        $this->cnc->execute_query("INSERT INTO configs (upload_id, config_name, filters_json) VALUES (?, ?, ?)", [$upload_id, $config_name, json_encode($filters)]);
    }

    function get_upload_configs($upload_id): array {
        $configs = $this->cnc->execute_query("SELECT * FROM configs WHERE upload_id=?", [$upload_id])->fetch_all(MYSQLI_ASSOC);
        return $configs;
    }

    // update upload path in database
    function update_uploads($name, $filepath): void {
        $this->cnc->execute_query("INSERT INTO uploads (owner_id, upload_name, upload_path) VALUES (?, ?, ?)", [$_SESSION["uid"], $name, $filepath]);
    }

    // function parse_upload($name): string {
        
    // }

    function parse_upload($file_name) {
        // all the data gets made ready for display ONLY when you upload, to save a ton of performance

        // first, we prepare the file
        $file = $_FILES["uploaded_file"];
        $file_tmp = $file["tmp_name"];

        // check if the file type is okay
        if ($file["type"] != "text/csv") {
            return "Only CSV files are accepted. Please, upload a different file.";
        } 

        // check if the fields in the CSV are correct
        $tmp_stream = fopen($file_tmp, "r");
        $field_names = fgetcsv($tmp_stream, 1000); // gets first line
        $correct_field_names = [
            0 => "Date",
            1 => "Time",
            2 => "Card Nr.",
            3 => "Vehicle Nr.",
            4 => "Product",
            5 => "Amount",
            6 => "Total sum",
            7 => "Currency",
            8 => "Country",
            9 => "Country ISO",
            10 => "Fuel station"
        ];
        foreach ($field_names as $field_idx => $field_value) {
            $correct_field = $correct_field_names[$field_idx];
            if ($field_value != $correct_field) {
                return "The column name nr. " . $field_idx + 1 ." '$field_value' is incorrect. It should be '$correct_field.'";
            }
        }

        // get the raw data first
        $raw_data = [];
        while (($data = fgetcsv($tmp_stream, 1000)) !== false) {
            $raw_data[] = $data;
        } fclose($tmp_stream);

        // now we make the data ready for display
        $rates = json_decode(file_get_contents("http://open.er-api.com/v6/latest/EUR"), true)["rates"];
        
        $cached_unit_ids = [];
        $noinfo_unit_ids = [];
        $cached_car_data = [];
        $ret = [];
        foreach ($raw_data as $row_idx => $row) {
            $insert = [];
            $current_fuel = false;
            foreach ($row as $field_idx => $field) {
                switch ($field_idx) {
                    case 1: // time
                    case 8: // country
                    case 9: // country_iso
                        continue 2;
                        break;
                    case 0: // date & time
                        $insert["datetime"] = time_zone_convert($row[0] . " " . $row[1], $row[9]);
                        break;
                    case 2: // fuel card nr
                        $insert["fuel_card"] = $field;
                        break;
                    case 3: // vehicle nr
                        $insert["vehicle"] = $field;
                        break;
                    case 4: // fuel type
                        $fuel_type = "";

                        if (in_array($field, ["Diesel", "D Miles", "Premium Diesel"])) {
                            $fuel_type = "Diesel"; $current_fuel = true;
                        }
    
                        if (in_array($field, ["E95", "95 Miles", "FUTURA 95"])) {
                            $fuel_type = "Gasoline, 95"; $current_fuel = true;
                        }
    
                        if (in_array($field, ["E98", "Super Plus 98", "98E0 milesPLUS"])) {
                            $fuel_type = "Gasoline, 98"; $current_fuel = true;
                        }
    
                        if (in_array($field, ["Electricity", "Charging", "Self-service charge station"])) {
                            $fuel_type = "Electricity"; $current_fuel = true;
                        }
    
                        if (in_array($field, ["Extra premium CNG", "CNG", "BIOCNG"])) {
                            $fuel_type = "CNG (compressed natural gas)"; $current_fuel = true;
                        }

                        $insert["fuel_type"] = $fuel_type;
                        break;
                    case 5: // total amount filled
                        if ($insert["fuel_type"] == "Electricity") {
                            $insert["amount"] = str_replace(",", ".", $field) . " kWh";
                        }
                        if (in_array($insert["fuel_type"], ["Gasoline, 95", "Gasoline, 98", "Diesel"])) {
                            $insert["amount"] = str_replace(",", ".", $field) . " L";
                        }
                        if ($insert["fuel_type"] == "CNG (compressed natural gas)") {
                            $insert["amount"] = str_replace(",", ".", $field) . " kg";
                        }
                        break;
                    case 6: // total cost (eur)
                        if ($row[7] != "EUR") {
                            $eur_price = round(floatval($field) * floatval($rates[$row[7]]), 2);
                            $insert["total_sum"] = $eur_price;
                        } else {
                            $insert["total_sum"] = str_replace(",", ".", $field);
                        }
                        break;
                    case 7: // currency field, but we put in the per_unit cost so its after total cost
                        // total cost per liter / weight / unit
                        if ($current_fuel)
                            $insert["per_unit"] = round(floatval($insert["total_sum"]) / floatval(explode(" ", $insert["amount"])[0]), 2);
                        break;
                    case 10: // fuel station
                        $insert["fuel_station"] = $field;
                        break;
                }
            }

            // if the transaction is not a fuel transaction, then dont add to the return array
            if (!$current_fuel)
                continue;

            // required api fields
            {
                $key = "f94e281f9eff169647620454a2f62839524452a8";
                $vehicle_nr = $insert["vehicle"];
                
                // first, we check if we already checked if this vehicle has valid data in the api
                // this way, each unique car number will have API called on it only once, to save performance
                if (!array_key_exists($vehicle_nr, $cached_unit_ids) && !array_key_exists($vehicle_nr, $noinfo_unit_ids)) {
                    // we could in theory call the whole car list one time and get from that, but considering in the actual api thats not meant for this task,
                    // there are ~ 200k cars (judging from the unit_id), that would not be viable at all because the file would be way too big
                    // so this solution is adaptable to the real api
                    $list_resp = json_decode(file_get_contents("https://mapon.com/api/v1/unit/list.json?key=$key&car_number=$vehicle_nr"), true);
                    if (isset($list_resp["data"]["units"])) {
                        $cached_unit_ids[$vehicle_nr] = $list_resp["data"]["units"][0]["unit_id"];
                    } else {
                        $noinfo_unit_ids[$vehicle_nr] = 0;
                    }
                }

                $datetime_arr = explode(" ", $insert["datetime"]);
                $date = str_replace(".", "-", $datetime_arr[0]);
                $time = $datetime_arr[1];
                
                // then, we check if current vehicles number is in the valid unit id array and its data isnt cached
                if (array_key_exists($vehicle_nr, $cached_unit_ids) && !array_key_exists($vehicle_nr . $date . $time, $cached_car_data)) {
                    $unit_id = $cached_unit_ids[$vehicle_nr];

                    $history_resp = json_decode(file_get_contents("https://mapon.com/api/v1/unit_data/history_point.json?key=$key&unit_id=$unit_id&datetime={$date}T{$time}Z&include[]=can_total_distance&include[]=mileage&include[]=position"), true);
                    $cached_car_data[$vehicle_nr . $date . $time] = [
                        "can_distance" => $history_resp["data"]["units"][0]["can_total_distance"]["value"],
                        "gps_distance" => $history_resp["data"]["units"][0]["mileage"]["value"],
                        "position"     => $history_resp["data"]["units"][0]["position"]["value"]
                    ];
                }

                // if the data exists, we add it to the insert array for display
                if (array_key_exists($vehicle_nr . $date . $time, $cached_car_data)) {
                    $car_data = $cached_car_data[$vehicle_nr . $date . $time];

                    $insert["can_distance"] = $car_data["can_distance"];
                    $insert["gps_distance"] = $car_data["gps_distance"];
                    $insert["position_lat"] = $car_data["position"]["lat"];
                    $insert["position_lng"] = $car_data["position"]["lng"];
                }
            }

            // add row to result array
            array_push($ret, $insert);
        }

        $generated_filename = uniqid( );
        $target_file = "../uploads/$generated_filename.json";

        $given_name = $file_name;
        if ($given_name == "") {
            $given_name = "Upload " . date("Y-m-d");
        }

        file_put_contents($target_file, json_encode($ret), JSON_PRETTY_PRINT); // put the data as json into it

        $this->update_uploads($given_name, $target_file);

        // redirect("index.php");
        return "File uploaded successfully, please return to the home screen to perform actions.";
    }
}

// enum field: int {
//     case date = 0;
//     case time = 1;
//     case card_nr = 2;
//     case vehicle_nr = 3;
//     case product = 4;
//     case amount = 5;
//     case total_sum = 6;
//     case currency = 7;
//     case country = 8;
//     case country_iso = 9;
//     case fuel_station = 10;
// }

function time_zone_convert($fromTime, $country, $format = 'Y.m.d H:i:s') {
    $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $country);
    $from = new DateTimeZone($timezone[0]);

    $to = new DateTimeZone("Europe/Riga");

    $orgTime = new DateTime($fromTime, $from);

    $toTime = new DateTime($orgTime->format("c"));

    $toTime->setTimezone($to);

    return $toTime->format($format);
}
class csv {
    // filters_args needs to be structured like this (indexes and structure matter)
    // those arguments that aren't added will simply be ignored
    /*
    $filter_args = [
        "filter_date" => [begin_date, end_date],
        "filter_fueltype" => fueltype_value,
        "filter_fuelcard" => fuelcard_value,
        "filter_vehiclenr" => vehiclenr_value,
        "sort_by" => sort_by_value
    ];
    */
    static function filter($json_data, $filter_args) {
        $filtered = [];

        $total_diesel = 0;
        $total_gasoline95 = 0;
        $total_gasoline98 = 0;
        $total_cng = 0;
        $total_electricity = 0;

        foreach ($json_data as $row_idx => $row) {
            // filter by period
            if (array_key_exists("filter_date", $filter_args)) {
                $datetime = $row["datetime"]; // get date and time field
                $date = explode(" ", $datetime)[0]; // get date by seperating the date from datetime field

                $timestamp_begin = strtotime($filter_args["filter_date"][0]);
                $timestamp_end = strtotime($filter_args["filter_date"][1]);
                $timestamp_this = strtotime(str_replace(".", "/", $date));

                if ($timestamp_this < $timestamp_begin || $timestamp_this > $timestamp_end) {
                    continue;
                }
            }

            // if requested fueltype is not in this transaction, dont add (same goes for the other 2 if's below)
            if (array_key_exists("filter_fueltype", $filter_args)) {
                if ($row["fuel_type"] != $filter_args["filter_fueltype"]) {
                    continue;
                }
            }

            if (array_key_exists("filter_fuelcard", $filter_args)) {
                if ($row["fuel_card"] != $filter_args["filter_fuelcard"]) {
                    continue;
                }
            }

            if (array_key_exists("filter_vehiclenr", $filter_args)) {
                if ($row["vehicle"] != $filter_args["filter_vehiclenr"]) {
                    continue;
                }
            }

            switch ($row["fuel_type"]) { // for summary
                case "Diesel":
                    $total_diesel += $row["total_sum"];
                    break;
                case "Gasoline, 95":
                    $total_gasoline95 += $row["total_sum"];
                    break;
                case "Gasoline, 98":
                    $total_gasoline98 += $row["total_sum"];
                    break;
                case "CNG (compressed natural gas)":
                    $total_cng += $row["total_sum"];
                    break;
                case "Electricity":
                    $total_electricity += $row["total_sum"];
                    break;
            }

            // add row to result array
            array_push($filtered, $row);
        }

        // here we handle sorting
        if (isset($filter_args["sort_by"])) {
            if ($filter_args["sort_by"] == "Sort by date, ascending") {
                usort($filtered, function($a, $b) {
                    return new DateTime(str_replace(".", "/", $a["datetime"])) <=> new DateTime(str_replace(".", "/", $b["datetime"]));
                });
            }

            if ($filter_args["sort_by"] == "Sort by date, descending") {
                usort($filtered, function($a, $b) {
                    return new DateTime(str_replace(".", "/", $b["datetime"])) <=> new DateTime(str_replace(".", "/", $a["datetime"]));
                });
            }

            if ($filter_args["sort_by"] == "Sort by fuel type, ascending") {
                usort($filtered, function($a, $b) {
                    return $a["fuel_type"] <=> $b["fuel_type"];
                });
            }

            if ($filter_args["sort_by"] == "Sort by fuel type, descending") {
                usort($filtered, function($a, $b) {
                    return $b["fuel_type"] <=> $a["fuel_type"];
                });
            }
        }
        
        // gather all summary data
        $summary_data = [
            "amount" => sizeof($filtered),
            "total_all" => $total_diesel + $total_cng + $total_gasoline95 + $total_gasoline98 + $total_electricity,
            "total_diesel" => $total_diesel,
            "total_cng" => $total_cng,
            "total_gasoline95" => $total_gasoline95,
            "total_gasoline98" => $total_gasoline98,
            "total_electricity" => $total_electricity
        ];

        return ["data" => $filtered, "summary" => $summary_data];
    }
}
