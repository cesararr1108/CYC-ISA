<?php
session_start();

foreach($_SESSION as $key => $s){
    unset($_SESSION[$key]);  // Aquí necesitas la clave
}
header('Location: login.php');