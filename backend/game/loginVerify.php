<?php
session_start();
include("../db.php");
if ( (isset($_POST['gameSection'])) && (isset($_POST['gameInstructor'])) && (isset($_POST['gameTeam'])) ){
    $section = htmlentities($_POST['gameSection']);
    $instructor = htmlentities($_POST['gameInstructor']);
    $team = htmlentities($_POST['gameTeam']);

    $query = "SELECT gameId, gameActive, gameRedJoined, gameBlueJoined FROM GAMES WHERE gameSection = ? AND gameInstructor = ?";
    $preparedQuery = $db->prepare($query);
    $preparedQuery->bind_param("ss", $section,$instructor);
    $preparedQuery->execute();
    $results = $preparedQuery->get_result();
    $numResults = $results->num_rows;

    if ($numResults == 1){
        $r = $results->fetch_assoc();

        $_SESSION['myTeam'] = $team;
        $_SESSION['gameId'] = $r['gameId'];
        $_SESSION['gameSection'] = $section;
        $_SESSION['gameInstructor'] = $instructor;

        //Check if the game is activated by the teacher
        if ($r['gameActive'] == 0) {
            header("location:../../home.php?gameErr=4");
            exit;
        }

        //Check if not already logged in, and update the database
        if ($team == "Red") {
            if ($r['gameRedJoined'] == 1) {
                header("location:../../home.php?gameErr=2");
                exit;
            }
            $query = 'UPDATE games SET gameRedJoined = ? WHERE (gameId = ?)';
            $query = $db->prepare($query);
            $joinedValue = 1;
            $query->bind_param("ii", $joinedValue, $r['gameId']);
            $query->execute();
        } else if ($team == "Blue") {
            if ($r['gameBlueJoined'] == 1) {
                header("location:../../home.php?gameErr=3");
                exit;
            }
            $query = 'UPDATE games SET gameBlueJoined = ? WHERE (gameId = ?)';
            $query = $db->prepare($query);
            $joinedValue = 1;
            $query->bind_param("ii", $joinedValue, $r['gameId']);
            $query->execute();
        } else if ($team == "Spec") {
            //do nothing for spectator
        } else {
            header("location:../../home.php?gameErr=7");  //bad value for team
            exit;
        }

        $query8 = "SELECT updateId FROM updates WHERE updateGameId = ? ORDER BY updateId DESC";
        $preparedQuery8 = $db->prepare($query8);
        $preparedQuery8->bind_param("i", $gameId);
        $preparedQuery8->execute();
        $results8 = $preparedQuery8->get_result();
        $num_results8 = $results8->num_rows;
        if ($num_results8 == 0) {
            $_SESSION['lastUpdateId'] = 0;
        } else {
            $r8= $results8->fetch_assoc();
            $_SESSION['lastUpdateId'] = $r8['updateId'];
        }



        //Store the matrices in the session TODO: change how these are stored, as constants that aren't looked up (but need to store them on the server itself for multiple clients? / threads?)
        if (($handle = fopen('../matrices/adjMatrix.csv', "r")) !== FALSE) {
            $counter = 0;
            while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $arraySize = count($data);
                for ($i=0; $i < $arraySize; $i++) {
                    $_SESSION['dist'][$counter][$i] = $data[$i];
                }
                $counter++;
            }
        }
        fclose($handle);
        for ($k = 0; $k < $arraySize; ++$k) {
            for ($i = 0; $i < $arraySize; ++$i) {
                for ($j = 0; $j < $arraySize; ++$j) {
                    if (($_SESSION['dist'][$i][$k] * $_SESSION['dist'][$k][$j] != 0) && ($i != $j)) {
                        if (($_SESSION['dist'][$i][$k] + $_SESSION['dist'][$k][$j] < $_SESSION['dist'][$i][$j]) || ($_SESSION['dist'][$i][$j] == 0)) {
                            $_SESSION['dist'][$i][$j] = $_SESSION['dist'][$i][$k] + $_SESSION['dist'][$k][$j];
                        }
                    }
                }
            }
        }
        if (($handle = fopen('../matrices/attackMatrix.csv', "r")) !== FALSE) {
            $counter = 0;
            while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                $arraySize = count($data);
                for ($i=0; $i < $arraySize; $i++) {
                    $_SESSION['attack'][$counter][$i] = $data[$i];
                }
                $counter++;
            }
        }
        fclose($handle);

        header("location:../../game.php");

    } else {
        header("location:../../home.php?gameErr=5");  //game does not exist or multiple games
    }
} else {
    header("location:../../home.php?gameErr=1");  //did not send all of the submit values to log in
}


$db->close();

